<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Seniority level of a job, e.g. "Junior" or "Team Lead".
 *
 * Carries the months of experience so that a job can derive its schema.org
 * experienceRequirements from the assigned level.
 */
class Level extends AbstractEntity
{
    protected string $title = '';
    protected string $slug = '';
    protected string $description = '';
    protected int $monthsOfExperience = 0;

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

    public function getMonthsOfExperience(): int
    {
        return $this->monthsOfExperience;
    }

    public function setMonthsOfExperience(int $monthsOfExperience): void
    {
        $this->monthsOfExperience = $monthsOfExperience;
    }
}
