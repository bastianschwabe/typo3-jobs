<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\Domain\Model;

use BastianSchwabe\Jobs\Domain\Dto\JobDemand;
use BastianSchwabe\Jobs\Domain\Model\Job;
use BastianSchwabe\Jobs\Domain\Repository\JobRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Editors translate the wording of a job; everything else has to stay put.
 *
 * Relations, dates and the machine-readable structured-data values carry
 * l10n_mode "exclude", which makes DataHandler copy them into the translation
 * instead of leaving it empty. Without that, the German JSON-LD would lose the
 * hiringOrganization and jobLocation that Google requires.
 */
final class JobTranslationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    private JobRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/jobs.csv');

        $this->subject = $this->get(JobRepository::class);

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $querySettings->setLanguageAspect(new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $this->subject->setDefaultQuerySettings($querySettings);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function untranslatableJobFieldProvider(): array
    {
        $fields = [
            'level', 'occupational_field', 'locations', 'contacts', 'company_profiles',
            'date_posted', 'valid_through', 'employment_type', 'identifier',
            'job_location_type', 'applicant_location_requirements',
            'base_salary_currency', 'base_salary_unit', 'base_salary_value',
        ];

        return array_combine($fields, array_map(static fn(string $f): array => [$f], $fields));
    }

    #[Test]
    #[DataProvider('untranslatableJobFieldProvider')]
    public function fieldIsExcludedFromTranslation(string $field): void
    {
        self::assertSame(
            'exclude',
            $GLOBALS['TCA']['tx_jobs_domain_model_job']['columns'][$field]['l10n_mode'] ?? null,
            sprintf('Field "%s" would diverge between languages.', $field),
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function translatableJobFieldProvider(): array
    {
        $fields = ['title', 'teaser', 'description', 'education_requirements', 'experience_requirements'];

        return array_combine($fields, array_map(static fn(string $f): array => [$f], $fields));
    }

    #[Test]
    #[DataProvider('translatableJobFieldProvider')]
    public function editorialFieldStaysTranslatable(string $field): void
    {
        self::assertArrayNotHasKey(
            'l10n_mode',
            $GLOBALS['TCA']['tx_jobs_domain_model_job']['columns'][$field],
            sprintf('Field "%s" must stay translatable.', $field),
        );
    }

    #[Test]
    public function translatedJobUsesTheGermanWording(): void
    {
        self::assertSame('Backend-Entwickler:in', $this->germanJob()->getTitle());
    }

    #[Test]
    public function translatedJobKeepsItsRelations(): void
    {
        $job = $this->germanJob();

        self::assertSame(2, $job->getLevel()?->getUid(), 'The level was lost in translation.');
        self::assertSame(1, $job->getOccupationalField()?->getUid(), 'The field of work was lost in translation.');

        $cities = [];
        foreach ($job->getLocations() as $location) {
            $cities[] = $location->getCity();
        }
        self::assertSame(['Berlin'], $cities, 'The job location was lost in translation.');
    }

    #[Test]
    public function translatedJobKeepsItsStructuredDataValues(): void
    {
        $job = $this->germanJob();

        self::assertSame('FULL_TIME', $job->getEmploymentType());
        self::assertSame('2026-03-02', $job->getDatePosted()?->format('Y-m-d'));
    }

    private function germanJob(): Job
    {
        foreach ($this->subject->findByDemand(new JobDemand(), new \DateTimeImmutable('2026-05-01')) as $job) {
            if ($job->getUid() === 1) {
                return $job;
            }
        }

        self::fail('The translated job was not returned for the German language.');
    }
}
