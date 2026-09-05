<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Statistic;

use BastianSchwabe\Jobs\Enum\ApplyStatus;
use BastianSchwabe\Jobs\Enum\ApplyType;

/**
 * One row of tx_jobs_apply_statistic: a click on the "apply" proxy.
 */
final readonly class JobApplyStatistic
{
    public function __construct(
        public int $uid,
        public int $job,
        public string $sessionHash,
        public ApplyType $type,
        public ApplyStatus $status,
        public \DateTimeImmutable $createdAt,
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
            type: ApplyType::tryFrom((string)$row['type']) ?? ApplyType::Url,
            status: ApplyStatus::tryFrom((string)$row['status']) ?? ApplyStatus::Started,
            createdAt: new \DateTimeImmutable('@' . (int)$row['crdate']),
        );
    }
}
