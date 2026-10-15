<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\Service;

use BastianSchwabe\Jobs\Domain\Model\Job;
use BastianSchwabe\Jobs\Domain\Repository\JobRepository;
use BastianSchwabe\Jobs\Service\ApplicationFormResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ApplicationFormResolverTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    private ApplicationFormResolver $subject;

    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/application_form.csv');

        $repository = $this->get(JobRepository::class);
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $repository->setDefaultQuerySettings($querySettings);

        $this->subject = new ApplicationFormResolver($repository);
        $this->now = new \DateTimeImmutable('2026-05-01 12:00:00');
    }

    #[Test]
    #[DataProvider('unusableUids')]
    public function rejectsAnythingThatIsNotAnOpenJob(mixed $uid): void
    {
        self::assertNull($this->subject->resolveJob($uid, $this->now));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unusableUids(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'zero' => [0],
            'negative' => [-1],
            'text' => ['1 OR 1=1'],
            'array' => [[1]],
            'unknown' => [99],
            'expired' => [5],
            'hidden' => [6],
        ];
    }

    #[Test]
    public function acceptsAnOpenJobGivenAsStringOrInteger(): void
    {
        self::assertSame('With address', $this->subject->resolveJob('1', $this->now)?->getTitle());
        self::assertSame('With address', $this->subject->resolveJob(1, $this->now)?->getTitle());
    }

    #[Test]
    public function describesAJobWithItsReference(): void
    {
        self::assertSame('With address (REF-1)', $this->subject->describe($this->job(1)));
        self::assertSame('Contact only', $this->subject->describe($this->job(2)));
    }

    #[Test]
    public function theApplicationAddressWinsOverTheContacts(): void
    {
        $job = $this->job(1);

        self::assertSame(['jobs@example.org' => ''], $this->subject->resolveRecipients($job));
    }

    #[Test]
    public function fallsBackToTheFirstContactWithAnAddress(): void
    {
        $job = $this->job(2);

        self::assertSame(['berta@example.org' => 'Berta Beispiel'], $this->subject->resolveRecipients($job));
    }

    #[Test]
    public function anInvalidApplicationAddressFallsBackToTheContacts(): void
    {
        $job = $this->job(4);

        self::assertSame(['berta@example.org' => 'Berta Beispiel'], $this->subject->resolveRecipients($job));
    }

    #[Test]
    public function aJobWithoutAnyAddressLeavesTheChoiceToTheForm(): void
    {
        $job = $this->job(3);

        self::assertSame([], $this->subject->resolveRecipients($job));
    }

    private function job(int $uid): Job
    {
        $job = $this->subject->resolveJob($uid, $this->now);
        self::assertNotNull($job);

        return $job;
    }
}
