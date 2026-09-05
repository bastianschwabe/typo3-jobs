<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Repository;

use BastianSchwabe\Jobs\Domain\Statistic\JobApplyStatistic;
use BastianSchwabe\Jobs\Enum\ApplyStatus;
use BastianSchwabe\Jobs\Enum\ApplyType;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class JobApplyStatisticRepository
{
    public const TABLE = 'tx_jobs_apply_statistic';

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
    ) {}

    public function log(int $job, string $sessionHash, ApplyType $type, ApplyStatus $status, int $timestamp): void
    {
        $this->connection()->insert(self::TABLE, [
            'job' => $job,
            'session_hash' => $sessionHash,
            'type' => $type->value,
            'status' => $status->value,
            'crdate' => $timestamp,
            'tstamp' => $timestamp,
        ]);
    }

    /**
     * @return array<int, array<string, int>> job uid => status value => count
     */
    public function countByJobAndStatus(): array
    {
        $queryBuilder = $this->connection()->createQueryBuilder();
        $rows = $queryBuilder
            ->select('job', 'status')
            ->addSelectLiteral('COUNT(*) AS ' . $queryBuilder->quoteIdentifier('amount'))
            ->from(self::TABLE)
            ->groupBy('job', 'status')
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['job']][(string)$row['status']] = (int)$row['amount'];
        }

        return $result;
    }

    /**
     * @return list<JobApplyStatistic>
     */
    public function findByJob(int $job): array
    {
        $queryBuilder = $this->connection()->createQueryBuilder();
        $rows = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->eq('job', $queryBuilder->createNamedParameter($job, Connection::PARAM_INT)))
            ->orderBy('crdate', 'DESC')
            ->addOrderBy('uid', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(JobApplyStatistic::fromRow(...), $rows);
    }

    public function truncate(): void
    {
        $this->connection()->truncate(self::TABLE);
    }

    private function connection(): Connection
    {
        return $this->connectionPool->getConnectionForTable(self::TABLE);
    }
}
