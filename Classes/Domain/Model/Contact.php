<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Person to contact about a job. Rendered in the frontend only; JobPosting has
 * no property for it, so it is deliberately kept out of the JSON-LD output.
 */
class Contact extends AbstractEntity
{
    protected string $firstName = '';
    protected string $lastName = '';
    protected string $role = '';
    protected string $description = '';
    protected string $phone = '';
    protected string $email = '';
    protected string $urlWebsite = '';
    protected string $urlLinkedin = '';
    protected string $urlXing = '';
    protected ?FileReference $photo = null;

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
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

    public function getPhoto(): ?FileReference
    {
        return $this->photo;
    }

    public function setPhoto(?FileReference $photo): void
    {
        $this->photo = $photo;
    }
}
