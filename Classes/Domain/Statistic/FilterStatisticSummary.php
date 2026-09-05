<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Statistic;

/**
 * One distinct filter combination and how often it was used. The uids are
 * already resolved to labels; an empty label means "not part of this
 * combination". Only lists with at least one active filter are recorded, so
 * at least one value is always set.
 */
final readonly class FilterStatisticSummary
{
    public function __construct(
        public string $filterHash,
        public string $level,
        public string $occupationalField,
        public string $location,
        public string $employmentType,
        public bool $remoteOnly,
        public string $search,
        public int $count,
        public float $averageResults,
        public \DateTimeImmutable $lastUsedAt,
    ) {}
}
