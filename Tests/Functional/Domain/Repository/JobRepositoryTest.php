<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\Domain\Repository;

use BastianSchwabe\Jobs\Domain\Dto\JobDemand;
use BastianSchwabe\Jobs\Domain\Model\Job;
use BastianSchwabe\Jobs\Domain\Repository\JobRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class JobRepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    private JobRepository $subject;

    /** Reference date: jobs 1, 2 and 4 are open, job 3 expired on 2026-04-01. */
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/jobs.csv');

        $this->subject = $this->get(JobRepository::class);

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->subject->setDefaultQuerySettings($querySettings);

        $this->now = new \DateTimeImmutable('2026-05-01 12:00:00');
    }

    #[Test]
    public function returnsOnlyVisibleAndUnexpiredJobs(): void
    {
        self::assertSame(
            ['Backend Developer', 'Marketing Manager', 'Remote Designer'],
            $this->titlesOf($this->subject->findByDemand(new JobDemand(), $this->now)),
        );
    }

    #[Test]
    public function filtersByLevel(): void
    {
        $demand = new JobDemand();
        $demand->setLevel(2);

        self::assertSame(['Backend Developer'], $this->titlesOf($this->subject->findByDemand($demand, $this->now)));
    }

    #[Test]
    public function filtersByOccupationalField(): void
    {
        $demand = new JobDemand();
        $demand->setOccupationalField(2);

        self::assertSame(
            ['Marketing Manager', 'Remote Designer'],
            $this->titlesOf($this->subject->findByDemand($demand, $this->now)),
        );
    }

    #[Test]
    public function filtersByLocationAcrossTheMmRelation(): void
    {
        $demand = new JobDemand();
        $demand->setLocation(2);

        self::assertSame(['Marketing Manager'], $this->titlesOf($this->subject->findByDemand($demand, $this->now)));
    }

    #[Test]
    public function filtersByEmploymentType(): void
    {
        $demand = new JobDemand();
        $demand->setEmploymentType('PART_TIME');

        self::assertSame(
            ['Marketing Manager', 'Remote Designer'],
            $this->titlesOf($this->subject->findByDemand($demand, $this->now)),
        );
    }

    #[Test]
    public function filtersRemoteJobs(): void
    {
        $demand = new JobDemand();
        $demand->setRemoteOnly(true);

        self::assertSame(['Remote Designer'], $this->titlesOf($this->subject->findByDemand($demand, $this->now)));
    }

    #[Test]
    public function searchesTitleTeaserAndDescription(): void
    {
        $demand = new JobDemand();
        $demand->setSearch('Pixels');

        self::assertSame(['Remote Designer'], $this->titlesOf($this->subject->findByDemand($demand, $this->now)));
    }

    #[Test]
    public function combinesSeveralFilters(): void
    {
        $demand = new JobDemand();
        $demand->setOccupationalField(2);
        $demand->setEmploymentType('PART_TIME');
        $demand->setRemoteOnly(true);

        self::assertSame(['Remote Designer'], $this->titlesOf($this->subject->findByDemand($demand, $this->now)));
    }

    #[Test]
    public function expiredJobIsIncludedOnItsLastDay(): void
    {
        $lastDay = new \DateTimeImmutable('2026-04-01 18:00:00');

        self::assertContains(
            'Expired Position',
            $this->titlesOf($this->subject->findByDemand(new JobDemand(), $lastDay)),
        );
    }

    /**
     * @param iterable<Job> $jobs
     * @return list<string>
     */
    private function titlesOf(iterable $jobs): array
    {
        $titles = [];
        foreach ($jobs as $job) {
            $titles[] = $job->getTitle();
        }
        sort($titles);

        return $titles;
    }
}
