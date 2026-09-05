<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Service\Tracking;

use BastianSchwabe\Jobs\Domain\Repository\JobApplyStatisticRepository;
use BastianSchwabe\Jobs\Domain\Repository\JobFilterStatisticRepository;
use BastianSchwabe\Jobs\Domain\Repository\JobViewStatisticRepository;
use BastianSchwabe\Jobs\Domain\Statistic\FilterStatisticSummary;
use BastianSchwabe\Jobs\Domain\Statistic\JobStatisticSummary;
use BastianSchwabe\Jobs\Enum\ApplyStatus;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;

/**
 * Read side of the tracking tables: everything the statistics module and the
 * dashboard widget display.
 */
class StatisticService
{
    public function __construct(
        protected readonly JobViewStatisticRepository $viewRepository,
        protected readonly JobApplyStatisticRepository $applyRepository,
        protected readonly JobFilterStatisticRepository $filterRepository,
        protected readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * One summary per default-language job, plus one for every job that has
     * statistics but no record any more. Sorted by views, then applications.
     *
     * @return list<JobStatisticSummary>
     */
    public function getJobSummaries(): array
    {
        $views = $this->viewRepository->countByJob();
        $applies = $this->applyRepository->countByJobAndStatus();
        $titles = $this->fetchJobTitles();

        $uids = array_unique([...array_keys($titles), ...array_keys($views), ...array_keys($applies)]);

        $summaries = [];
        foreach ($uids as $uid) {
            $summaries[] = new JobStatisticSummary(
                jobUid: $uid,
                title: $titles[$uid] ?? '',
                exists: isset($titles[$uid]),
                views: $views[$uid]['views'] ?? 0,
                sessions: $views[$uid]['sessions'] ?? 0,
                appliesStarted: $applies[$uid][ApplyStatus::Started->value] ?? 0,
                appliesCompleted: $applies[$uid][ApplyStatus::Completed->value] ?? 0,
            );
        }

        usort($summaries, static fn(JobStatisticSummary $a, JobStatisticSummary $b): int =>
            [$b->views, $b->appliesStarted, $a->title] <=> [$a->views, $a->appliesStarted, $b->title]);

        return $summaries;
    }

    /**
     * @return list<JobStatisticSummary>
     */
    public function getTopViewed(int $limit = 5): array
    {
        $summaries = array_filter($this->getJobSummaries(), static fn(JobStatisticSummary $s): bool => $s->views > 0);

        return array_slice(array_values($summaries), 0, $limit);
    }

    /**
     * @return list<JobStatisticSummary>
     */
    public function getTopApplied(int $limit = 5): array
    {
        $summaries = array_filter($this->getJobSummaries(), static fn(JobStatisticSummary $s): bool => $s->appliesStarted > 0);
        usort($summaries, static fn(JobStatisticSummary $a, JobStatisticSummary $b): int =>
            [$b->appliesStarted, $b->views, $a->title] <=> [$a->appliesStarted, $a->views, $b->title]);

        return array_slice($summaries, 0, $limit);
    }

    /**
     * The two "top 5" charts of the statistics module and the dashboard
     * widget, ready for a template: label, value and bar length in percent.
     *
     * @return array{views: list<array{uid: int, label: string, value: int, percent: int}>, applies: list<array{uid: int, label: string, value: int, percent: int}>}
     */
    public function getTopCharts(int $limit = 5): array
    {
        return [
            'views' => $this->toChartItems($this->getTopViewed($limit), static fn(JobStatisticSummary $s): int => $s->views),
            'applies' => $this->toChartItems($this->getTopApplied($limit), static fn(JobStatisticSummary $s): int => $s->appliesStarted),
        ];
    }

    /**
     * @param list<JobStatisticSummary> $summaries
     * @param callable(JobStatisticSummary): int $value
     * @return list<array{uid: int, label: string, value: int, percent: int}>
     */
    private function toChartItems(array $summaries, callable $value): array
    {
        $max = 0;
        foreach ($summaries as $summary) {
            $max = max($max, $value($summary));
        }

        $items = [];
        foreach ($summaries as $summary) {
            $items[] = [
                'uid' => $summary->jobUid,
                'label' => $summary->title,
                'value' => $value($summary),
                'percent' => $max > 0 ? (int)round($value($summary) / $max * 100) : 0,
            ];
        }

        return $items;
    }

    /**
     * Distinct filter combinations with the uids resolved to record titles.
     *
     * @return list<FilterStatisticSummary>
     */
    public function getFilterSummaries(): array
    {
        $levels = $this->fetchTitles('tx_jobs_domain_model_level', 'title');
        $fields = $this->fetchTitles('tx_jobs_domain_model_occupational_field', 'title');
        $locations = $this->fetchTitles('tx_jobs_domain_model_location', 'name');

        $summaries = [];
        foreach ($this->filterRepository->findAggregated() as $row) {
            $summaries[] = new FilterStatisticSummary(
                filterHash: $row['filter_hash'],
                level: $this->label($levels, $row['level']),
                occupationalField: $this->label($fields, $row['occupational_field']),
                location: $this->label($locations, $row['location']),
                employmentType: $row['employment_type'],
                remoteOnly: $row['remote_only'],
                search: $row['search'],
                count: $row['count'],
                averageResults: $row['average_results'],
                lastUsedAt: new \DateTimeImmutable('@' . $row['last_used']),
            );
        }

        return $summaries;
    }

    /**
     * @return array<int, string> uid => title of every live default-language job
     */
    public function fetchJobTitles(): array
    {
        return $this->fetchTitles('tx_jobs_domain_model_job', 'title');
    }

    /**
     * @return array<int, string>
     */
    private function fetchTitles(string $table, string $labelField): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
            ->add(new WorkspaceRestriction(0));

        $rows = $queryBuilder
            ->select('uid', $labelField)
            ->from($table)
            ->where($queryBuilder->expr()->eq('sys_language_uid', 0))
            ->executeQuery()
            ->fetchAllAssociative();

        $titles = [];
        foreach ($rows as $row) {
            $titles[(int)$row['uid']] = (string)$row[$labelField];
        }

        return $titles;
    }

    /**
     * @param array<int, string> $titles
     */
    private function label(array $titles, int $uid): string
    {
        if ($uid === 0) {
            return '';
        }

        return $titles[$uid] ?? '#' . $uid;
    }
}
