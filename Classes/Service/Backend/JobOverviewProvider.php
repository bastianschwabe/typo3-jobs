<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Service\Backend;

use BastianSchwabe\Jobs\Domain\Repository\JobApplyStatisticRepository;
use BastianSchwabe\Jobs\Domain\Repository\JobViewStatisticRepository;
use BastianSchwabe\Jobs\Enum\ApplyStatus;
use BastianSchwabe\Jobs\Enum\JobListFilter;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;

/**
 * Rows of the "Jobs" backend module: every default-language job with its
 * classification, access state and tracking counters.
 *
 * @phpstan-type OverviewRow array{
 *     uid: int,
 *     pid: int,
 *     title: string,
 *     identifier: string,
 *     level: string,
 *     occupationalField: string,
 *     locations: list<string>,
 *     datePosted: ?\DateTimeImmutable,
 *     validThrough: ?\DateTimeImmutable,
 *     status: string,
 *     active: bool,
 *     expired: bool,
 *     views: int,
 *     sessions: int,
 *     applies: int,
 * }
 */
class JobOverviewProvider
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ENDED = 'ended';

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly JobViewStatisticRepository $viewRepository,
        protected readonly JobApplyStatisticRepository $applyRepository,
        protected readonly Context $context,
    ) {}

    /**
     * @return list<OverviewRow>
     */
    public function getJobs(JobListFilter $filter): array
    {
        $now = (int)$this->context->getPropertyFromAspect('date', 'timestamp');
        $today = (new \DateTimeImmutable('@' . $now))->setTime(0, 0)->getTimestamp();

        $locations = $this->fetchLocationNames();
        $views = $this->viewRepository->countByJob();
        $applies = $this->applyRepository->countByJobAndStatus();

        $rows = [];
        foreach ($this->fetchJobs() as $row) {
            $uid = (int)$row['uid'];
            $status = $this->determineStatus($row, $now);
            $active = $status === self::STATUS_ACTIVE;

            if ($filter === JobListFilter::Active && !$active) {
                continue;
            }
            if ($filter === JobListFilter::Inactive && $active) {
                continue;
            }

            $validThrough = (int)($row['valid_through'] ?? 0);

            $rows[] = [
                'uid' => $uid,
                'pid' => (int)$row['pid'],
                'title' => (string)$row['title'],
                'identifier' => (string)$row['identifier'],
                'level' => (string)($row['level_title'] ?? ''),
                'occupationalField' => (string)($row['field_title'] ?? ''),
                'locations' => $locations[$uid] ?? [],
                'datePosted' => $this->date((int)$row['date_posted']),
                'validThrough' => $this->date($validThrough),
                'status' => $status,
                'active' => $active,
                'expired' => $validThrough > 0 && $validThrough < $today,
                'views' => $views[$uid]['views'] ?? 0,
                'sessions' => $views[$uid]['sessions'] ?? 0,
                'applies' => $applies[$uid][ApplyStatus::Started->value] ?? 0,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchJobs(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jobs_domain_model_job');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
            ->add(new WorkspaceRestriction(0));

        return $queryBuilder
            ->select(
                'job.uid',
                'job.pid',
                'job.title',
                'job.identifier',
                'job.date_posted',
                'job.valid_through',
                'job.hidden',
                'job.starttime',
                'job.endtime',
            )
            ->addSelectLiteral(
                $queryBuilder->quoteIdentifier('level.title') . ' AS ' . $queryBuilder->quoteIdentifier('level_title'),
                $queryBuilder->quoteIdentifier('field.title') . ' AS ' . $queryBuilder->quoteIdentifier('field_title'),
            )
            ->from('tx_jobs_domain_model_job', 'job')
            ->leftJoin(
                'job',
                'tx_jobs_domain_model_level',
                'level',
                $queryBuilder->expr()->eq('level.uid', $queryBuilder->quoteIdentifier('job.level')),
            )
            ->leftJoin(
                'job',
                'tx_jobs_domain_model_occupational_field',
                'field',
                $queryBuilder->expr()->eq('field.uid', $queryBuilder->quoteIdentifier('job.occupational_field')),
            )
            ->where($queryBuilder->expr()->eq('job.sys_language_uid', 0))
            ->orderBy('job.date_posted', 'DESC')
            ->addOrderBy('job.title', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return array<int, list<string>> job uid => location names
     */
    private function fetchLocationNames(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jobs_domain_model_location');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());

        $rows = $queryBuilder
            ->select('mm.uid_local', 'location.name')
            ->from('tx_jobs_job_location_mm', 'mm')
            ->join(
                'mm',
                'tx_jobs_domain_model_location',
                'location',
                $queryBuilder->expr()->eq('location.uid', $queryBuilder->quoteIdentifier('mm.uid_foreign')),
            )
            ->where($queryBuilder->expr()->eq('location.sys_language_uid', 0))
            ->orderBy('mm.sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $names = [];
        foreach ($rows as $row) {
            $names[(int)$row['uid_local']][] = (string)$row['name'];
        }

        return $names;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function determineStatus(array $row, int $now): string
    {
        if ((int)$row['hidden'] === 1) {
            return self::STATUS_HIDDEN;
        }
        if ((int)$row['starttime'] > $now) {
            return self::STATUS_SCHEDULED;
        }
        if ((int)$row['endtime'] > 0 && (int)$row['endtime'] <= $now) {
            return self::STATUS_ENDED;
        }

        return self::STATUS_ACTIVE;
    }

    private function date(int $timestamp): ?\DateTimeImmutable
    {
        return $timestamp > 0 ? new \DateTimeImmutable('@' . $timestamp) : null;
    }
}
