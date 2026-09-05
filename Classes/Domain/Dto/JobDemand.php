<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Dto;

/**
 * Filter criteria for the job list, populated from GET parameters.
 *
 * Plain properties rather than an entity, so Extbase can map the request
 * arguments without touching the persistence layer.
 */
class JobDemand
{
    protected int $level = 0;
    protected int $occupationalField = 0;
    protected int $location = 0;
    protected string $employmentType = '';
    protected bool $remoteOnly = false;
    protected string $search = '';

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getOccupationalField(): int
    {
        return $this->occupationalField;
    }

    public function setOccupationalField(int $occupationalField): void
    {
        $this->occupationalField = $occupationalField;
    }

    public function getLocation(): int
    {
        return $this->location;
    }

    public function setLocation(int $location): void
    {
        $this->location = $location;
    }

    public function getEmploymentType(): string
    {
        return $this->employmentType;
    }

    public function setEmploymentType(string $employmentType): void
    {
        $this->employmentType = $employmentType;
    }

    public function isRemoteOnly(): bool
    {
        return $this->remoteOnly;
    }

    public function setRemoteOnly(bool $remoteOnly): void
    {
        $this->remoteOnly = $remoteOnly;
    }

    public function getSearch(): string
    {
        return $this->search;
    }

    public function setSearch(string $search): void
    {
        $this->search = trim($search);
    }

    /** Used by the template to decide whether a "reset filters" link makes sense. */
    public function isActive(): bool
    {
        return $this->level > 0
            || $this->occupationalField > 0
            || $this->location > 0
            || $this->employmentType !== ''
            || $this->remoteOnly
            || $this->search !== '';
    }
}
