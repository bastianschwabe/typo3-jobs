<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\Service\Tracking;

use BastianSchwabe\Jobs\Domain\Statistic\FilterStatisticSummary;
use BastianSchwabe\Jobs\Domain\Statistic\JobStatisticSummary;
use BastianSchwabe\Jobs\Service\Tracking\StatisticService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class StatisticServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    private StatisticService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/jobs.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tracking.csv');
        $this->subject = $this->get(StatisticService::class);
    }

    #[Test]
    public function summariesCoverEveryJobAndOrphanedStatistics(): void
    {
        $summaries = $this->subject->getJobSummaries();
        $byUid = $this->byUid($summaries);

        // Job 999 has statistics but no record; jobs 3, 4 and 5 have a record
        // but no statistics. The translation (10) does not appear on its own.
        self::assertSame([999, 1, 2, 3, 5, 4], array_keys($byUid), 'sorted by views, then title');
        self::assertArrayNotHasKey(10, $byUid);

        self::assertSame(4, $byUid[1]->views);
        self::assertSame(2, $byUid[1]->sessions);
        self::assertSame(1, $byUid[1]->appliesStarted);
        self::assertSame(50.0, $byUid[1]->getConversionRate());

        self::assertSame(2, $byUid[2]->appliesStarted);
        self::assertSame(1, $byUid[2]->appliesCompleted);

        self::assertFalse($byUid[999]->exists);
        self::assertTrue($byUid[3]->exists);
        self::assertFalse($byUid[3]->hasData());
    }

    #[Test]
    public function topChartsScaleBarsToTheLargestValue(): void
    {
        $charts = $this->subject->getTopCharts(2);

        self::assertSame([999, 1], array_column($charts['views'], 'uid'));
        self::assertSame([100, 57], array_column($charts['views'], 'percent'));

        self::assertSame([2, 1], array_column($charts['applies'], 'uid'));
        self::assertSame([2, 1], array_column($charts['applies'], 'value'));
        self::assertSame('Marketing Manager', $charts['applies'][0]['label']);
    }

    #[Test]
    public function filterSummariesResolveUidsToTitlesAndGroupByHash(): void
    {
        $summaries = $this->subject->getFilterSummaries();
        $byHash = [];
        foreach ($summaries as $summary) {
            $byHash[$summary->filterHash] = $summary;
        }

        self::assertCount(3, $summaries);
        self::assertSame('hash-dev-berlin', $summaries[0]->filterHash, 'most used first');

        $devBerlin = $byHash['hash-dev-berlin'];
        self::assertSame(2, $devBerlin->count);
        self::assertSame('Development', $devBerlin->occupationalField);
        self::assertSame('Berlin office', $devBerlin->location);
        self::assertSame('', $devBerlin->level);
        self::assertSame(3.0, $devBerlin->averageResults);
        self::assertSame(1780000500, $devBerlin->lastUsedAt->getTimestamp());

        self::assertSame('Senior', $byHash['hash-senior']->level);
        self::assertSame('', $byHash['hash-senior']->location);

        $search = $byHash['hash-search'];
        self::assertSame('typo3', $search->search);
        self::assertSame('PART_TIME', $search->employmentType);
        self::assertTrue($search->remoteOnly);
        self::assertInstanceOf(FilterStatisticSummary::class, $search);
    }

    /**
     * @param list<JobStatisticSummary> $summaries
     * @return array<int, JobStatisticSummary>
     */
    private function byUid(array $summaries): array
    {
        $result = [];
        foreach ($summaries as $summary) {
            $result[$summary->jobUid] = $summary;
        }

        return $result;
    }
}
