<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * The hiring company. Maps to schema.org/Organization and supplies the
 * hiringOrganization of a job, which Google requires.
 */
class CompanyProfile extends AbstractEntity
{
    protected string $title = '';
    protected string $teaser = '';
    protected string $description = '';
    protected string $urlWebsite = '';
    protected string $urlLinkedin = '';
    protected string $urlXing = '';
    protected string $legalName = '';
    protected ?\DateTimeImmutable $foundingDate = null;
    protected int $numberOfEmployees = 0;
    protected ?Location $location = null;
    protected ?FileReference $logo = null;

    /** @var ObjectStorage<FileReference> */
    protected ObjectStorage $images;

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
        if (!isset($this->images)) {
            $this->images = new ObjectStorage();
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

    public function getUrlWebsite(): string
    {
        return $this->urlWebsite;
    }

    public function setUrlWebsite(string $urlWebsite): void
    {
        $this->urlWebsite = $urlWebsite;
    }

    public function getUrlLinkedin(): string
    {
        return $this->urlLinkedin;
    }

    public function setUrlLinkedin(string $urlLinkedin): void
    {
        $this->urlLinkedin = $urlLinkedin;
    }

    public function getUrlXing(): string
    {
        return $this->urlXing;
    }

    public function setUrlXing(string $urlXing): void
    {
        $this->urlXing = $urlXing;
    }

    /**
     * Social profiles as schema.org sameAs, skipping the ones left empty.
     *
     * @return list<string>
     */
    public function getSameAs(): array
    {
        return array_values(array_filter([
            $this->urlWebsite,
            $this->urlLinkedin,
            $this->urlXing,
        ], static fn(string $url): bool => $url !== ''));
    }

    public function getLegalName(): string
    {
        return $this->legalName;
    }

    public function setLegalName(string $legalName): void
    {
        $this->legalName = $legalName;
    }

    public function getFoundingDate(): ?\DateTimeImmutable
    {
        return $this->foundingDate;
    }

    public function setFoundingDate(?\DateTimeImmutable $foundingDate): void
    {
        $this->foundingDate = $foundingDate;
    }

    public function getNumberOfEmployees(): int
    {
        return $this->numberOfEmployees;
    }

    public function setNumberOfEmployees(int $numberOfEmployees): void
    {
        $this->numberOfEmployees = $numberOfEmployees;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): void
    {
        $this->location = $location;
    }

    public function getLogo(): ?FileReference
    {
        return $this->logo;
    }

    public function setLogo(?FileReference $logo): void
    {
        $this->logo = $logo;
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
}
