<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Service;

use BastianSchwabe\Jobs\Domain\Dto\SchemaContext;
use BastianSchwabe\Jobs\Domain\Model\CompanyProfile;
use BastianSchwabe\Jobs\Domain\Model\Job;
use BastianSchwabe\Jobs\Domain\Model\Location;

/**
 * Turns a job into a schema.org/JobPosting structure ready for JSON-LD.
 *
 * Optional properties are omitted rather than emitted empty: Google treats an
 * empty value as malformed, while a missing one is merely absent.
 *
 * @see https://developers.google.com/search/docs/appearance/structured-data/job-posting
 */
final readonly class JobPostingSchemaBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Job $job, SchemaContext $context = new SchemaContext()): array
    {
        $data = [
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => $job->getTitle(),
            'description' => $job->getDescription(),
            'datePosted' => $job->getDatePosted()?->format('Y-m-d'),
            'hiringOrganization' => $this->buildHiringOrganization($job, $context),
            'jobLocation' => $this->buildJobLocations($job),
        ];

        if ($job->getValidThrough() !== null) {
            // End of day: the posting stays valid throughout its last day.
            $data['validThrough'] = $job->getValidThrough()->setTime(23, 59, 59)->format(\DATE_ATOM);
        }

        if ($job->isRemote()) {
            $data['jobLocationType'] = 'TELECOMMUTE';
        }

        $applicantLocations = $this->buildApplicantLocationRequirements($job);
        if ($applicantLocations !== []) {
            $data['applicantLocationRequirements'] = $this->unwrapSingle($applicantLocations);
        }

        $employmentTypes = array_map(
            static fn($case): string => $case->value,
            $job->getEmploymentTypes(),
        );
        if ($employmentTypes !== []) {
            $data['employmentType'] = $this->unwrapSingle($employmentTypes);
        }

        if ($job->getIdentifier() !== '') {
            $data['identifier'] = [
                '@type' => 'PropertyValue',
                'name' => $this->resolveOrganizationName($job, $context),
                'value' => $job->getIdentifier(),
            ];
        }

        if ($context->jobUrl !== '') {
            $data['url'] = $context->jobUrl;
        }

        if ($job->isDirectApply()) {
            $data['directApply'] = true;
        }

        $educationRequirements = $this->buildEducationRequirements($job);
        if ($educationRequirements !== null) {
            $data['educationRequirements'] = $educationRequirements;
        }

        $experienceRequirements = $this->buildExperienceRequirements($job);
        if ($experienceRequirements !== null) {
            $data['experienceRequirements'] = $experienceRequirements;
        }

        if ($job->getEffectiveIndustry() !== '') {
            $data['industry'] = $job->getEffectiveIndustry();
        }

        if ($job->getEffectiveOccupationalCategory() !== '') {
            $data['occupationalCategory'] = $job->getEffectiveOccupationalCategory();
        }

        if ($job->hasBaseSalary()) {
            $data['baseSalary'] = $this->buildBaseSalary($job);
        }

        if ($context->imageUrls !== []) {
            $data['image'] = $this->unwrapSingle($context->imageUrls);
        }

        return array_filter(
            $data,
            static fn($value): bool => $value !== null && $value !== '' && $value !== [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHiringOrganization(Job $job, SchemaContext $context): array
    {
        $profile = $job->getPrimaryCompanyProfile();
        if ($profile === null) {
            // Fall back to the site-wide organization so the required property
            // is never missing just because an editor forgot to assign one.
            return array_filter([
                '@type' => 'Organization',
                'name' => $context->organizationName,
                'url' => $context->organizationUrl,
                'logo' => $context->organizationLogoUrl,
            ], static fn($value): bool => $value !== '');
        }

        return array_filter([
            '@type' => 'Organization',
            'name' => $profile->getTitle(),
            'legalName' => $profile->getLegalName(),
            'url' => $profile->getUrlWebsite() !== '' ? $profile->getUrlWebsite() : $context->organizationUrl,
            'logo' => $context->organizationLogoUrl,
            'sameAs' => $this->unwrapSingle($profile->getSameAs()),
            'address' => $this->buildPostalAddress($profile),
            'numberOfEmployees' => $profile->getNumberOfEmployees() > 0
                ? ['@type' => 'QuantitativeValue', 'value' => $profile->getNumberOfEmployees()]
                : null,
            'foundingDate' => $profile->getFoundingDate()?->format('Y-m-d'),
        ], static fn($value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildPostalAddress(CompanyProfile $profile): ?array
    {
        $location = $profile->getLocation();

        return $location === null ? null : $this->buildAddress($location);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildJobLocations(Job $job): array
    {
        $places = [];
        foreach ($job->getLocations() as $location) {
            $details = array_filter([
                'name' => $location->getName(),
                'address' => $this->buildAddress($location),
                'geo' => $location->hasCoordinates() ? [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $location->getLatitude(),
                    'longitude' => $location->getLongitude(),
                ] : null,
            ], static fn($value): bool => $value !== null && $value !== '' && $value !== []);

            // A Place carrying nothing but its @type tells search engines nothing.
            if ($details !== []) {
                $places[] = ['@type' => 'Place'] + $details;
            }
        }

        return $places;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAddress(Location $location): array
    {
        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => trim($location->getStreet() . ' ' . $location->getAddressAddition()),
            'addressLocality' => $location->getCity(),
            'postalCode' => $location->getZip(),
            'addressRegion' => $location->getAddressRegion(),
            'addressCountry' => $location->getAddressCountry(),
        ], static fn($value): bool => $value !== '');
    }

    /**
     * @return list<array<string, string>>
     */
    private function buildApplicantLocationRequirements(Job $job): array
    {
        return array_map(
            static fn(string $country): array => ['@type' => 'Country', 'name' => $country],
            $job->getApplicantLocationRequirementsList(),
        );
    }

    /**
     * @return array<string, mixed>|string|null
     */
    private function buildEducationRequirements(Job $job): array|string|null
    {
        $category = $job->getEducationCategoryEnum();
        if ($category !== null) {
            return [
                '@type' => 'EducationalOccupationalCredential',
                'credentialCategory' => $category->value,
            ];
        }

        // Free text is still valid schema.org, it just is not machine readable.
        return $job->getEducationRequirements() !== '' ? $job->getEducationRequirements() : null;
    }

    /**
     * @return array<string, mixed>|string|null
     */
    private function buildExperienceRequirements(Job $job): array|string|null
    {
        $months = $job->getEffectiveExperienceMonths();
        if ($months > 0) {
            return array_filter([
                '@type' => 'OccupationalExperienceRequirements',
                'monthsOfExperience' => $months,
                'experienceInPlaceOfEducation' => $job->isExperienceInPlaceOfEducation() ?: null,
            ], static fn($value): bool => $value !== null);
        }

        return $job->getExperienceRequirements() !== '' ? $job->getExperienceRequirements() : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBaseSalary(Job $job): array
    {
        $value = ['@type' => 'QuantitativeValue', 'unitText' => $job->getBaseSalaryUnit()];

        if ($job->getBaseSalaryValue() > 0.0) {
            $value['value'] = $job->getBaseSalaryValue();
        } else {
            if ($job->getBaseSalaryMin() > 0.0) {
                $value['minValue'] = $job->getBaseSalaryMin();
            }
            if ($job->getBaseSalaryMax() > 0.0) {
                $value['maxValue'] = $job->getBaseSalaryMax();
            }
        }

        return [
            '@type' => 'MonetaryAmount',
            'currency' => $job->getBaseSalaryCurrency(),
            'value' => $value,
        ];
    }

    private function resolveOrganizationName(Job $job, SchemaContext $context): string
    {
        return $job->getPrimaryCompanyProfile()?->getTitle() ?: $context->organizationName;
    }

    /**
     * schema.org accepts a single value or a list; a one-element list is
     * unwrapped to keep the output close to Google's own examples.
     *
     * @param list<mixed> $values
     */
    private function unwrapSingle(array $values): mixed
    {
        return count($values) === 1 ? $values[0] : $values;
    }
}
