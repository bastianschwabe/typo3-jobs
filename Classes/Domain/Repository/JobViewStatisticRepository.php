<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Repository;

use BastianSchwabe\Jobs\Domain\Statistic\JobViewStatistic;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * tx_jobs_view_statistic has no TCA, so the table is addressed with plain
 * query builders instead of the Extbase persistence layer.
 */
class JobViewStatisticRepository
{
    public const TABLE = 'tx_jobs_view_statistic';

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Counts one view: increments the counter of the (job, session) pair, or
     * creates the pair when the session sees the job for the first time.
     */
    public function track(int $job, string $sessionHash, int $timestamp): void
    {
        if ($this->incrementCounter($job, $sessionHash, $timestamp) > 0) {
            return;
        }

        try {
            $this->connection()->insert(self::TABLE, [
                'job' => $job,
                'session_hash' => $sessionHash,
                'counter' => 1,
                'crdate' => $timestamp,
                'tstamp' => $timestamp,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Two requests of the same session raced for the first row. The
            // other one won, so this one only has to add its count.
            $this->incrementCounter($job, $sessionHash, $timestamp);
        }
    }

    /**
     * @return array<int, array{views: int, sessions: int}> keyed by job uid
     */
    public function countByJob(): array
    {
        $queryBuilder = $this->connection()->createQueryBuilder();
        $rows = $queryBuilder
            ->select('job')
            ->addSelectLiteral(
                'SUM(' . $queryBuilder->quoteIdentifier('counter') . ') AS ' . $queryBuilder->quoteIdentifier('views'),
                'COUNT(*) AS ' . $queryBuilder->quoteIdentifier('sessions'),
            )
            ->from(self::TABLE)
            ->groupBy('job')
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['job']] = [
                'views' => (int)$row['views'],
                'sessions' => (int)$row['sessions'],
            ];
        }

        return $result;
    }

    /**
     * @return list<JobViewStatistic>
     */
    public function findByJob(int $job): array
    {
        $queryBuilder = $this->connection()->createQueryBuilder();
        $rows = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->eq('job', $queryBuilder->createNamedParameter($job, Connection::PARAM_INT)))
            ->orderBy('tstamp', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(JobViewStatistic::fromRow(...), $rows);
    }

    public function truncate(): void
    {
        $this->connection()->truncate(self::TABLE);
    }

    private function incrementCounter(int $job, string $sessionHash, int $timestamp): int
    {
        $queryBuilder = $this->connection()->createQueryBuilder();

        return (int)$queryBuilder
            ->update(self::TABLE)
            ->set('counter', $queryBuilder->quoteIdentifier('counter') . ' + 1', false)
            ->set('tstamp', $timestamp)
            ->where(
                $queryBuilder->expr()->eq('job', $queryBuilder->createNamedParameter($job, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('session_hash', $queryBuilder->createNamedParameter($sessionHash)),
            )
            ->executeStatement();
    }

    private function connection(): Connection
    {
        return $this->connectionPool->getConnectionForTable(self::TABLE);
    }
}
