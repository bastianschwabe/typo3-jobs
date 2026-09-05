<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Statistic;

/**
 * One row of tx_jobs_filter_statistic: a rendered job list together with the
 * filter values that produced it.
 */
final readonly class JobFilterStatistic
{
    public function __construct(
        public int $uid,
        public string $sessionHash,
        public string $filterHash,
        public int $level,
        public int $occupationalField,
        public int $location,
        public string $employmentType,
        public bool $remoteOnly,
        public string $search,
        public int $resultCount,
        public int $language,
        public \DateTimeImmutable $createdAt,
    ) {}

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            uid: (int)$row['uid'],
            sessionHash: (string)$row['session_hash'],
            filterHash: (string)$row['filter_hash'],
            level: (int)$row['level'],
            occupationalField: (int)$row['occupational_field'],
            location: (int)$row['location'],
            employmentType: (string)$row['employment_type'],
            remoteOnly: (bool)$row['remote_only'],
            search: (string)$row['search'],
            resultCount: (int)$row['result_count'],
            language: (int)$row['language'],
            createdAt: new \DateTimeImmutable('@' . (int)$row['crdate']),
        );
    }
}
