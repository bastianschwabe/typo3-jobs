<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\Service\Backend;

use BastianSchwabe\Jobs\Enum\JobListFilter;
use BastianSchwabe\Jobs\Service\Backend\JobOverviewProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class JobOverviewProviderTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    private JobOverviewProvider $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/jobs.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tracking.csv');

        // Job 3 expired on 2026-04-01; the reference date is a month later.
        $this->get(Context::class)->setAspect(
            'date',
            new DateTimeAspect(new \DateTimeImmutable('2026-05-01 12:00:00')),
        );

        $this->subject = $this->get(JobOverviewProvider::class);
    }

    #[Test]
    public function listsDefaultLanguageJobsWithRelationsAndCounters(): void
    {
        $rows = $this->subject->getJobs(JobListFilter::All);
        $byUid = array_column($rows, null, 'uid');

        // Newest first, equal dates by title; the translation (10) never shows.
        self::assertSame([1, 5, 2, 4, 3], array_keys($byUid));

        self::assertSame('Backend Developer', $byUid[1]['title']);
        self::assertSame('Senior', $byUid[1]['level']);
        self::assertSame('Development', $byUid[1]['occupationalField']);
        self::assertSame(['Berlin office'], $byUid[1]['locations']);
        self::assertSame(4, $byUid[1]['views']);
        self::assertSame(2, $byUid[1]['sessions']);
        self::assertSame(1, $byUid[1]['applies']);

        self::assertSame([], $byUid[4]['locations'], 'remote job has no location');
        self::assertSame(0, $byUid[4]['views']);
    }

    #[Test]
    public function statusFollowsTheAccessFieldsAndExpiryIsSeparate(): void
    {
        $byUid = array_column($this->subject->getJobs(JobListFilter::All), null, 'uid');

        self::assertSame(JobOverviewProvider::STATUS_ACTIVE, $byUid[1]['status']);
        self::assertFalse($byUid[1]['expired']);

        self::assertSame(JobOverviewProvider::STATUS_HIDDEN, $byUid[5]['status']);

        // Expired by validThrough, but access-wise still active.
        self::assertSame(JobOverviewProvider::STATUS_ACTIVE, $byUid[3]['status']);
        self::assertTrue($byUid[3]['expired']);
    }

    #[Test]
    public function filterSplitsByAccessState(): void
    {
        self::assertSame([1, 2, 4, 3], array_column($this->subject->getJobs(JobListFilter::Active), 'uid'));
        self::assertSame([5], array_column($this->subject->getJobs(JobListFilter::Inactive), 'uid'));
    }
}
