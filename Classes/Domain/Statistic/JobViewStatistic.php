<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Statistic;

/**
 * One row of tx_jobs_view_statistic: how often one frontend session opened
 * one job.
 *
 * Plain value object rather than an Extbase entity: the table has no TCA and
 * is never edited in the backend, so the persistence layer stays out of it.
 */
final readonly class JobViewStatistic
{
    public function __construct(
        public int $uid,
        public int $job,
        public string $sessionHash,
        public int $counter,
        public \DateTimeImmutable $firstViewedAt,
        public \DateTimeImmutable $lastViewedAt,
    ) {}

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            uid: (int)$row['uid'],
            job: (int)$row['job'],
            sessionHash: (string)$row['session_hash'],
            counter: (int)$row['counter'],
            firstViewedAt: new \DateTimeImmutable('@' . (int)$row['crdate']),
            lastViewedAt: new \DateTimeImmutable('@' . (int)$row['tstamp']),
        );
    }
}
