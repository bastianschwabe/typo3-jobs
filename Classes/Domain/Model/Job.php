<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Model;

use BastianSchwabe\Jobs\Enum\EducationCategory;
use BastianSchwabe\Jobs\Enum\EmploymentType;
use BastianSchwabe\Jobs\Enum\SalaryUnit;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A job posting. Maps to schema.org/JobPosting.
 *
 * Fields whose name matches a schema.org property hold the literal value that
 * ends up in the JSON-LD, so no translation table is needed when serializing.
 */
class Job extends AbstractEntity
{
    protected string $title = '';
    protected string $slug = '';
    protected string $teaser = '';
    protected string $description = '';
    protected ?\DateTimeImmutable $datePosted = null;
    protected ?\DateTimeImmutable $validThrough = null;

    /** Comma separated list of EmploymentType values. */
    protected string $employmentType = '';

    protected string $educationCategory = '';
    protected string $educationRequirements = '';
    protected int $experienceMonths = 0;
    protected bool $experienceInPlaceOfEducation = false;
    protected string $experienceRequirements = '';
    protected string $industry = '';
    protected string $occupationalCategory = '';
    protected string $identifier = '';
    protected bool $jobLocationType = false;

    /** Comma separated list of ISO 3166-1 alpha-2 country codes. */
    protected string $applicantLocationRequirements = '';

    protected bool $directApply = false;
    protected string $applicationUrl = '';
    protected string $applicationEmail = '';
    protected string $baseSalaryCurrency = '';
    protected string $baseSalaryUnit = '';
    protected float $baseSalaryValue = 0.0;
    protected float $baseSalaryMin = 0.0;
    protected float $baseSalaryMax = 0.0;
    protected ?Level $level = null;
    protected ?OccupationalField $occupationalField = null;

    /** @var ObjectStorage<Location> */
    protected ObjectStorage $locations;

    /** @var ObjectStorage<Contact> */
    protected ObjectStorage $contacts;

    /** @var ObjectStorage<CompanyProfile> */
    protected ObjectStorage $companyProfiles;

    /** @var ObjectStorage<FileReference> */
    protected ObjectStorage $images;

    /** @var ObjectStorage<FileReference> */
    protected ObjectStorage $downloads;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * Extbase reconstitutes entities without invoking the constructor, so the
     * storages are initialized here as well.
     */
    public function initializeObject(): void
    {
        if (!isset($this->locations)) {
            $this->locations = new ObjectStorage();
        }
        if (!isset($this->contacts)) {
            $this->contacts = new ObjectStorage();
        }
        if (!isset($this->companyProfiles)) {
            $this->companyProfiles = new ObjectStorage();
        }
        if (!isset($this->images)) {
            $this->images = new ObjectStorage();
        }
        if (!isset($this->downloads)) {
            $this->downloads = new ObjectStorage();
        }
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getTeaser(): string
    {
        return $this->teaser;
    }

    public function setTeaser(string $teaser): void
    {
        $this->teaser = $teaser;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDatePosted(): ?\DateTimeImmutable
    {
        return $this->datePosted;
    }

    public function setDatePosted(?\DateTimeImmutable $datePosted): void
    {
        $this->datePosted = $datePosted;
    }

    public function getValidThrough(): ?\DateTimeImmutable
    {
        return $this->validThrough;
    }

    public function setValidThrough(?\DateTimeImmutable $validThrough): void
    {
        $this->validThrough = $validThrough;
    }

    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        return $this->validThrough !== null
            && $this->validThrough < ($now ?? new \DateTimeImmutable());
    }

    public function getEmploymentType(): string
    {
        return $this->employmentType;
    }

    public function setEmploymentType(string $employmentType): void
    {
        $this->employmentType = $employmentType;
    }

    /**
     * Stored employment types, silently dropping values that are no longer part
     * of the schema.org vocabulary.
     *
     * @return list<EmploymentType>
     */
    public function getEmploymentTypes(): array
    {
        return array_values(array_filter(array_map(
            static fn(string $value): ?EmploymentType => EmploymentType::tryFrom(trim($value)),
            self::splitList($this->employmentType),
        )));
    }

    public function getEducationCategory(): string
    {
        return $this->educationCategory;
    }

    public function setEducationCategory(string $educationCategory): void
    {
        $this->educationCategory = $educationCategory;
    }

    public function getEducationCategoryEnum(): ?EducationCategory
    {
        return EducationCategory::tryFrom($this->educationCategory);
    }

    public function getEducationRequirements(): string
    {
        return $this->educationRequirements;
    }

    public function setEducationRequirements(string $educationRequirements): void
    {
        $this->educationRequirements = $educationRequirements;
    }

    public function getExperienceMonths(): int
    {
        return $this->experienceMonths;
    }

    public function setExperienceMonths(int $experienceMonths): void
    {
        $this->experienceMonths = $experienceMonths;
    }

    /** Falls back to the months configured on the assigned level. */
    public function getEffectiveExperienceMonths(): int
    {
        if ($this->experienceMonths > 0) {
            return $this->experienceMonths;
        }

        return $this->level?->getMonthsOfExperience() ?? 0;
    }

    public function isExperienceInPlaceOfEducation(): bool
    {
        return $this->experienceInPlaceOfEducation;
    }

    public function setExperienceInPlaceOfEducation(bool $experienceInPlaceOfEducation): void
    {
        $this->experienceInPlaceOfEducation = $experienceInPlaceOfEducation;
    }

    public function getExperienceRequirements(): string
    {
        return $this->experienceRequirements;
    }

    public function setExperienceRequirements(string $experienceRequirements): void
    {
        $this->experienceRequirements = $experienceRequirements;
    }

    public function getIndustry(): string
    {
        return $this->industry;
    }

    public function setIndustry(string $industry): void
    {
        $this->industry = $industry;
    }

    /** Falls back to the industry configured on the assigned field of work. */
    public function getEffectiveIndustry(): string
    {
        return $this->industry !== '' ? $this->industry : ($this->occupationalField?->getIndustry() ?? '');
    }

    public function getOccupationalCategory(): string
    {
        return $this->occupationalCategory;
    }

    public function setOccupationalCategory(string $occupationalCategory): void
    {
        $this->occupationalCategory = $occupationalCategory;
    }

    /** Falls back to the category configured on the assigned field of work. */
    public function getEffectiveOccupationalCategory(): string
    {
        return $this->occupationalCategory !== ''
            ? $this->occupationalCategory
            : ($this->occupationalField?->getOccupationalCategory() ?? '');
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function isJobLocationType(): bool
    {
        return $this->jobLocationType;
    }

    public function setJobLocationType(bool $jobLocationType): void
    {
        $this->jobLocationType = $jobLocationType;
    }

    public function isRemote(): bool
    {
        return $this->jobLocationType;
    }

    public function getApplicantLocationRequirements(): string
    {
        return $this->applicantLocationRequirements;
    }

    public function setApplicantLocationRequirements(string $applicantLocationRequirements): void
    {
        $this->applicantLocationRequirements = $applicantLocationRequirements;
    }

    /** @return list<string> */
    public function getApplicantLocationRequirementsList(): array
    {
        return self::splitList($this->applicantLocationRequirements);
    }

    public function isDirectApply(): bool
    {
        return $this->directApply;
    }

    public function setDirectApply(bool $directApply): void
    {
        $this->directApply = $directApply;
    }

    public function getApplicationUrl(): string
    {
        return $this->applicationUrl;
    }

    public function setApplicationUrl(string $applicationUrl): void
    {
        $this->applicationUrl = $applicationUrl;
    }

    public function getApplicationEmail(): string
    {
        return $this->applicationEmail;
    }

    public function setApplicationEmail(string $applicationEmail): void
    {
        $this->applicationEmail = $applicationEmail;
    }

    public function getBaseSalaryCurrency(): string
    {
        return $this->baseSalaryCurrency;
    }

    public function setBaseSalaryCurrency(string $baseSalaryCurrency): void
    {
        $this->baseSalaryCurrency = $baseSalaryCurrency;
    }

    public function getBaseSalaryUnit(): string
    {
        return $this->baseSalaryUnit;
    }

    public function setBaseSalaryUnit(string $baseSalaryUnit): void
    {
        $this->baseSalaryUnit = $baseSalaryUnit;
    }

    public function getBaseSalaryUnitEnum(): ?SalaryUnit
    {
        return SalaryUnit::tryFrom($this->baseSalaryUnit);
    }

    public function getBaseSalaryValue(): float
    {
        return $this->baseSalaryValue;
    }

    public function setBaseSalaryValue(float $baseSalaryValue): void
    {
        $this->baseSalaryValue = $baseSalaryValue;
    }

    public function getBaseSalaryMin(): float
    {
        return $this->baseSalaryMin;
    }

    public function setBaseSalaryMin(float $baseSalaryMin): void
    {
        $this->baseSalaryMin = $baseSalaryMin;
    }

    public function getBaseSalaryMax(): float
    {
        return $this->baseSalaryMax;
    }

    public function setBaseSalaryMax(float $baseSalaryMax): void
    {
        $this->baseSalaryMax = $baseSalaryMax;
    }

    /**
     * A salary is only usable for schema.org when a currency, a unit and at
     * least one amount are present.
     */
    public function hasBaseSalary(): bool
    {
        return $this->baseSalaryCurrency !== ''
            && $this->getBaseSalaryUnitEnum() !== null
            && ($this->baseSalaryValue > 0.0 || $this->baseSalaryMin > 0.0 || $this->baseSalaryMax > 0.0);
    }

    public function getLevel(): ?Level
    {
        return $this->level;
    }

    public function setLevel(?Level $level): void
    {
        $this->level = $level;
    }

    public function getOccupationalField(): ?OccupationalField
    {
        return $this->occupationalField;
    }

    public function setOccupationalField(?OccupationalField $occupationalField): void
    {
        $this->occupationalField = $occupationalField;
    }

    /** @return ObjectStorage<Location> */
    public function getLocations(): ObjectStorage
    {
        return $this->locations;
    }

    /** @param ObjectStorage<Location> $locations */
    public function setLocations(ObjectStorage $locations): void
    {
        $this->locations = $locations;
    }

    public function addLocation(Location $location): void
    {
        $this->locations->attach($location);
    }

    /** @return ObjectStorage<Contact> */
    public function getContacts(): ObjectStorage
    {
        return $this->contacts;
    }

    /** @param ObjectStorage<Contact> $contacts */
    public function setContacts(ObjectStorage $contacts): void
    {
        $this->contacts = $contacts;
    }

    public function addContact(Contact $contact): void
    {
        $this->contacts->attach($contact);
    }

    /** @return ObjectStorage<CompanyProfile> */
    public function getCompanyProfiles(): ObjectStorage
    {
        return $this->companyProfiles;
    }

    /** @param ObjectStorage<CompanyProfile> $companyProfiles */
    public function setCompanyProfiles(ObjectStorage $companyProfiles): void
    {
        $this->companyProfiles = $companyProfiles;
    }

    public function addCompanyProfile(CompanyProfile $companyProfile): void
    {
        $this->companyProfiles->attach($companyProfile);
    }

    /**
     * schema.org allows exactly one hiringOrganization, so the first assigned
     * profile wins.
     */
    public function getPrimaryCompanyProfile(): ?CompanyProfile
    {
        foreach ($this->companyProfiles as $companyProfile) {
            return $companyProfile;
        }

        return null;
    }

    /** @return ObjectStorage<FileReference> */
    public function getImages(): ObjectStorage
    {
        return $this->images;
    }

    /** @param ObjectStorage<FileReference> $images */
    public function setImages(ObjectStorage $images): void
    {
        $this->images = $images;
    }

    /** @return ObjectStorage<FileReference> */
    public function getDownloads(): ObjectStorage
    {
        return $this->downloads;
    }

    /** @param ObjectStorage<FileReference> $downloads */
    public function setDownloads(ObjectStorage $downloads): void
    {
        $this->downloads = $downloads;
    }

    /** @return list<string> */
    private static function splitList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), explode(',', $value)),
            static fn(string $item): bool => $item !== '',
        ));
    }
}
