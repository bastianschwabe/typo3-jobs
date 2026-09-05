<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * A place a job is performed at. Maps to schema.org/Place.
 */
class Location extends AbstractEntity
{
    protected string $name = '';
    protected string $street = '';
    protected string $addressAddition = '';
    protected string $zip = '';
    protected string $city = '';
    protected string $addressRegion = '';
    protected string $addressCountry = '';
    protected float $latitude = 0.0;
    protected float $longitude = 0.0;
    protected string $phone = '';
    protected string $email = '';
    protected string $urlWebsite = '';
    protected string $urlLinkedin = '';
    protected string $urlXing = '';
    protected ?FileReference $photo = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getAddressAddition(): string
    {
        return $this->addressAddition;
    }

    public function setAddressAddition(string $addressAddition): void
    {
        $this->addressAddition = $addressAddition;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getAddressRegion(): string
    {
        return $this->addressRegion;
    }

    public function setAddressRegion(string $addressRegion): void
    {
        $this->addressRegion = $addressRegion;
    }

    public function getAddressCountry(): string
    {
        return $this->addressCountry;
    }

    public function setAddressCountry(string $addressCountry): void
    {
        $this->addressCountry = $addressCountry;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    /** Only a complete coordinate pair is worth emitting as schema.org geo. */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== 0.0 && $this->longitude !== 0.0;
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
