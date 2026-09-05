<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Field of work a job belongs to, e.g. "Software Development".
 *
 * Supplies the schema.org occupationalCategory and industry defaults that a job
 * inherits unless it overrides them.
 */
class OccupationalField extends AbstractEntity
{
    protected string $title = '';
    protected string $slug = '';
    protected string $description = '';
    protected string $occupationalCategory = '';
    protected string $industry = '';

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getOccupationalCategory(): string
    {
        return $this->occupationalCategory;
    }

    public function setOccupationalCategory(string $occupationalCategory): void
    {
        $this->occupationalCategory = $occupationalCategory;
    }

    public function getIndustry(): string
    {
        return $this->industry;
    }

    public function setIndustry(string $industry): void
    {
        $this->industry = $industry;
    }
}
