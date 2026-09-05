<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Unit\Service;

use BastianSchwabe\Jobs\Domain\Dto\SchemaContext;
use BastianSchwabe\Jobs\Domain\Model\CompanyProfile;
use BastianSchwabe\Jobs\Domain\Model\Job;
use BastianSchwabe\Jobs\Domain\Model\Level;
use BastianSchwabe\Jobs\Domain\Model\Location;
use BastianSchwabe\Jobs\Domain\Model\OccupationalField;
use BastianSchwabe\Jobs\Enum\EmploymentType;
use BastianSchwabe\Jobs\Service\JobPostingSchemaBuilder;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class JobPostingSchemaBuilderTest extends UnitTestCase
{
    private JobPostingSchemaBuilder $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new JobPostingSchemaBuilder();
    }

    #[Test]
    public function emitsAllPropertiesGoogleRequires(): void
    {
        $result = $this->subject->build($this->createJob());

        foreach (['title', 'description', 'datePosted', 'hiringOrganization', 'jobLocation'] as $required) {
            self::assertArrayHasKey($required, $result, sprintf('Required property "%s" is missing', $required));
        }

        self::assertSame('https://schema.org/', $result['@context']);
        self::assertSame('JobPosting', $result['@type']);
        self::assertSame('2026-03-01', $result['datePosted']);
    }

    #[Test]
    public function omitsOptionalPropertiesThatAreEmpty(): void
    {
        $result = $this->subject->build($this->createJob());

        foreach (['baseSalary', 'validThrough', 'jobLocationType', 'directApply', 'identifier', 'image'] as $optional) {
            self::assertArrayNotHasKey($optional, $result, sprintf('Empty property "%s" should be omitted', $optional));
        }
    }

    #[Test]
    public function validThroughCoversTheWholeLastDay(): void
    {
        $job = $this->createJob();
        $job->setValidThrough(new \DateTimeImmutable('2026-06-30 00:00:00+00:00'));

        $result = $this->subject->build($job);

        self::assertSame('2026-06-30T23:59:59+00:00', $result['validThrough']);
    }

    #[Test]
    public function singleEmploymentTypeIsNotWrappedInAList(): void
    {
        $job = $this->createJob();
        $job->setEmploymentType(EmploymentType::FullTime->value);

        self::assertSame('FULL_TIME', $this->subject->build($job)['employmentType']);
    }

    #[Test]
    public function multipleEmploymentTypesKeepTheirSchemaValues(): void
    {
        $job = $this->createJob();
        $job->setEmploymentType('FULL_TIME,PART_TIME');

        self::assertSame(['FULL_TIME', 'PART_TIME'], $this->subject->build($job)['employmentType']);
    }

    #[Test]
    public function unknownEmploymentTypeIsDropped(): void
    {
        $job = $this->createJob();
        $job->setEmploymentType('FULL_TIME,NOT_A_SCHEMA_VALUE');

        self::assertSame('FULL_TIME', $this->subject->build($job)['employmentType']);
    }

    #[Test]
    public function remoteJobDeclaresTelecommuteAndEligibleCountries(): void
    {
        $job = $this->createJob();
        $job->setJobLocationType(true);
        $job->setApplicantLocationRequirements('DE,AT');

        $result = $this->subject->build($job);

        self::assertSame('TELECOMMUTE', $result['jobLocationType']);
        self::assertSame(
            [
                ['@type' => 'Country', 'name' => 'DE'],
                ['@type' => 'Country', 'name' => 'AT'],
            ],
            $result['applicantLocationRequirements'],
        );
    }

    #[Test]
    public function jobLocationCarriesAddressAndCoordinates(): void
    {
        $result = $this->subject->build($this->createJob());
        $place = $result['jobLocation'][0];

        self::assertSame('Place', $place['@type']);
        self::assertSame('Berlin', $place['address']['addressLocality']);
        self::assertSame('DE', $place['address']['addressCountry']);
        self::assertSame(52.52, $place['geo']['latitude']);
    }

    #[Test]
    public function coordinatesAreOmittedWhenIncomplete(): void
    {
        $job = $this->createJob();
        foreach ($job->getLocations() as $location) {
            $location->setLatitude(0.0);
            $location->setLongitude(0.0);
        }

        self::assertArrayNotHasKey('geo', $this->subject->build($job)['jobLocation'][0]);
    }

    #[Test]
    public function hiringOrganizationComesFromTheFirstCompanyProfile(): void
    {
        $result = $this->subject->build($this->createJob());

        self::assertSame('Neue Daten GmbH', $result['hiringOrganization']['name']);
        self::assertSame('https://example.org', $result['hiringOrganization']['url']);
    }

    #[Test]
    public function hiringOrganizationFallsBackToTheSiteSettings(): void
    {
        $job = $this->createJob();
        /** @var ObjectStorage<CompanyProfile> $noProfiles */
        $noProfiles = new ObjectStorage();
        $job->setCompanyProfiles($noProfiles);

        $result = $this->subject->build($job, new SchemaContext(
            organizationName: 'Fallback Ltd',
            organizationUrl: 'https://fallback.example',
        ));

        self::assertSame('Fallback Ltd', $result['hiringOrganization']['name']);
        self::assertSame('https://fallback.example', $result['hiringOrganization']['url']);
    }

    #[Test]
    public function fixedSalaryIsEmittedAsSingleValue(): void
    {
        $job = $this->createJob();
        $job->setBaseSalaryCurrency('EUR');
        $job->setBaseSalaryUnit('YEAR');
        $job->setBaseSalaryValue(65000.0);

        $salary = $this->subject->build($job)['baseSalary'];

        self::assertSame('MonetaryAmount', $salary['@type']);
        self::assertSame('EUR', $salary['currency']);
        self::assertSame('YEAR', $salary['value']['unitText']);
        self::assertSame(65000.0, $salary['value']['value']);
        self::assertArrayNotHasKey('minValue', $salary['value']);
    }

    #[Test]
    public function salaryRangeIsEmittedAsMinAndMax(): void
    {
        $job = $this->createJob();
        $job->setBaseSalaryCurrency('EUR');
        $job->setBaseSalaryUnit('MONTH');
        $job->setBaseSalaryMin(4000.0);
        $job->setBaseSalaryMax(5500.0);

        $value = $this->subject->build($job)['baseSalary']['value'];

        self::assertSame(4000.0, $value['minValue']);
        self::assertSame(5500.0, $value['maxValue']);
        self::assertArrayNotHasKey('value', $value);
    }

    #[Test]
    public function incompleteSalaryIsOmittedEntirely(): void
    {
        $job = $this->createJob();
        $job->setBaseSalaryValue(65000.0);

        self::assertArrayNotHasKey('baseSalary', $this->subject->build($job));
    }

    #[Test]
    public function educationCategoryBecomesACredential(): void
    {
        $job = $this->createJob();
        $job->setEducationCategory('bachelor degree');

        self::assertSame(
            ['@type' => 'EducationalOccupationalCredential', 'credentialCategory' => 'bachelor degree'],
            $this->subject->build($job)['educationRequirements'],
        );
    }

    #[Test]
    public function educationFallsBackToFreeTextWhenNoCategoryIsChosen(): void
    {
        $job = $this->createJob();
        $job->setEducationRequirements('Ausbildung im kaufmännischen Bereich');

        self::assertSame(
            'Ausbildung im kaufmännischen Bereich',
            $this->subject->build($job)['educationRequirements'],
        );
    }

    #[Test]
    public function experienceMonthsAreInheritedFromTheLevel(): void
    {
        $level = new Level();
        $level->setTitle('Senior');
        $level->setMonthsOfExperience(60);

        $job = $this->createJob();
        $job->setLevel($level);

        $result = $this->subject->build($job)['experienceRequirements'];

        self::assertSame('OccupationalExperienceRequirements', $result['@type']);
        self::assertSame(60, $result['monthsOfExperience']);
    }

    #[Test]
    public function experienceMonthsOnTheJobWinOverTheLevel(): void
    {
        $level = new Level();
        $level->setMonthsOfExperience(60);

        $job = $this->createJob();
        $job->setLevel($level);
        $job->setExperienceMonths(24);

        self::assertSame(24, $this->subject->build($job)['experienceRequirements']['monthsOfExperience']);
    }

    #[Test]
    public function industryAndCategoryAreInheritedFromTheFieldOfWork(): void
    {
        $field = new OccupationalField();
        $field->setIndustry('Information Technology');
        $field->setOccupationalCategory('15-1252.00');

        $job = $this->createJob();
        $job->setOccupationalField($field);

        $result = $this->subject->build($job);

        self::assertSame('Information Technology', $result['industry']);
        self::assertSame('15-1252.00', $result['occupationalCategory']);
    }

    #[Test]
    public function identifierIsNamedAfterTheHiringOrganization(): void
    {
        $job = $this->createJob();
        $job->setIdentifier('JOB-2026-042');

        self::assertSame(
            ['@type' => 'PropertyValue', 'name' => 'Neue Daten GmbH', 'value' => 'JOB-2026-042'],
            $this->subject->build($job)['identifier'],
        );
    }

    #[Test]
    public function contextSuppliesUrlAndImages(): void
    {
        $result = $this->subject->build($this->createJob(), new SchemaContext(
            jobUrl: 'https://example.org/jobs/developer',
            imageUrls: ['https://example.org/a.jpg', 'https://example.org/b.jpg'],
        ));

        self::assertSame('https://example.org/jobs/developer', $result['url']);
        self::assertSame(['https://example.org/a.jpg', 'https://example.org/b.jpg'], $result['image']);
    }

    #[Test]
    public function resultIsSerializableAsJson(): void
    {
        $json = json_encode($this->subject->build($this->createJob()));

        self::assertIsString($json);
        self::assertJson($json);
    }

    private function createJob(): Job
    {
        $location = new Location();
        $location->setName('Headquarters');
        $location->setStreet('Musterstraße 1');
        $location->setZip('10115');
        $location->setCity('Berlin');
        $location->setAddressRegion('Berlin');
        $location->setAddressCountry('DE');
        $location->setLatitude(52.52);
        $location->setLongitude(13.405);

        $company = new CompanyProfile();
        $company->setTitle('Neue Daten GmbH');
        $company->setUrlWebsite('https://example.org');

        $job = new Job();
        $job->setTitle('Backend Developer');
        $job->setDescription('<p>We are hiring.</p>');
        $job->setDatePosted(new \DateTimeImmutable('2026-03-01 00:00:00+00:00'));
        $job->addLocation($location);
        $job->addCompanyProfile($company);

        return $job;
    }
}
