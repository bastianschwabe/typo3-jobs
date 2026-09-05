<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\Service\Tracking;

use BastianSchwabe\Jobs\Domain\Dto\JobDemand;
use BastianSchwabe\Jobs\Domain\Repository\JobApplyStatisticRepository;
use BastianSchwabe\Jobs\Domain\Repository\JobFilterStatisticRepository;
use BastianSchwabe\Jobs\Domain\Repository\JobViewStatisticRepository;
use BastianSchwabe\Jobs\Enum\ApplyStatus;
use BastianSchwabe\Jobs\Enum\ApplyType;
use BastianSchwabe\Jobs\Service\Tracking\TrackingService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TrackingServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    private TrackingService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/jobs.csv');
        $this->subject = $this->get(TrackingService::class);
    }

    #[Test]
    public function viewsOfOneSessionShareOneRowAndCountUp(): void
    {
        $this->subject->trackView(1, 'session-a');
        $this->subject->trackView(1, 'session-a');
        $this->subject->trackView(1, 'session-b');

        $rows = $this->get(JobViewStatisticRepository::class)->findByJob(1);

        self::assertCount(2, $rows);
        self::assertSame(['session-a' => 2, 'session-b' => 1], $this->countersBySession($rows));
    }

    #[Test]
    public function viewsAreAggregatedPerJob(): void
    {
        $this->subject->trackView(1, 'session-a');
        $this->subject->trackView(1, 'session-a');
        $this->subject->trackView(1, 'session-b');
        $this->subject->trackView(2, 'session-b');

        self::assertSame(
            [1 => ['views' => 3, 'sessions' => 2], 2 => ['views' => 1, 'sessions' => 1]],
            $this->get(JobViewStatisticRepository::class)->countByJob(),
        );
    }

    #[Test]
    public function applyClicksAreLoggedWithTypeAndStatus(): void
    {
        $this->subject->trackApply(2, 'session-a', ApplyType::Email);
        $this->subject->trackApply(2, 'session-b', ApplyType::Url);
        $this->subject->trackApply(2, 'session-b', ApplyType::Form, ApplyStatus::Completed);

        $rows = $this->get(JobApplyStatisticRepository::class)->findByJob(2);
        self::assertCount(3, $rows);
        self::assertSame(ApplyType::Email, $rows[2]->type);
        self::assertSame(ApplyStatus::Started, $rows[2]->status);

        self::assertEquals(
            [2 => ['STARTED' => 2, 'COMPLETED' => 1]],
            $this->get(JobApplyStatisticRepository::class)->countByJobAndStatus(),
        );
    }

    /**
     * The statistics module groups identical filter combinations. The search
     * term is case-insensitive, everything else has to match exactly.
     */
    #[Test]
    public function identicalFilterCombinationsShareOneHash(): void
    {
        $first = new JobDemand();
        $first->setOccupationalField(1);
        $first->setSearch('TYPO3');

        $second = new JobDemand();
        $second->setOccupationalField(1);
        $second->setSearch('typo3');

        $third = new JobDemand();
        $third->setOccupationalField(2);
        $third->setSearch('typo3');

        $this->subject->trackFilter($first, 3, 'session-a');
        $this->subject->trackFilter($second, 3, 'session-b');
        $this->subject->trackFilter($third, 0, 'session-b', 1);
        $this->subject->trackFilter(new JobDemand(), 5, 'session-c');

        $aggregated = $this->get(JobFilterStatisticRepository::class)->findAggregated();

        self::assertCount(3, $aggregated);
        self::assertSame(2, $aggregated[0]['count']);
        self::assertSame(1, $aggregated[0]['occupational_field']);
        self::assertSame(3.0, $aggregated[0]['average_results']);
        self::assertSame(JobFilterStatisticRepository::hashDemand($first), $aggregated[0]['filter_hash']);
        self::assertSame(JobFilterStatisticRepository::hashDemand($second), $aggregated[0]['filter_hash']);
    }

    #[Test]
    public function translatedJobsCountTowardsTheirDefaultLanguageRecord(): void
    {
        self::assertSame(1, $this->subject->resolveTrackedJobUid(10), 'translation resolves to l10n_parent');
        self::assertSame(1, $this->subject->resolveTrackedJobUid(1), 'default language record stays');
    }

    #[Test]
    public function invisibleOrUnknownJobsAreNotTracked(): void
    {
        self::assertNull($this->subject->resolveTrackedJobUid(5), 'hidden job');
        self::assertNull($this->subject->resolveTrackedJobUid(4711), 'unknown uid');
        self::assertNull($this->subject->resolveTrackedJobUid(0));
    }

    #[Test]
    public function resetEmptiesEveryTrackingTable(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tracking.csv');

        $this->subject->resetAll();

        foreach ([
            JobViewStatisticRepository::TABLE,
            JobApplyStatisticRepository::TABLE,
            JobFilterStatisticRepository::TABLE,
        ] as $table) {
            self::assertSame(0, $this->countRows($table), sprintf('%s is not empty', $table));
        }
    }

    /**
     * @param list<\BastianSchwabe\Jobs\Domain\Statistic\JobViewStatistic> $rows
     * @return array<string, int>
     */
    private function countersBySession(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[$row->sessionHash] = $row->counter;
        }
        ksort($result);

        return $result;
    }

    private function countRows(string $table): int
    {
        return (int)$this->get(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->count('uid', $table, []);
    }
}
