<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Repository;

use BastianSchwabe\Jobs\Domain\Dto\JobDemand;
use BastianSchwabe\Jobs\Domain\Statistic\JobFilterStatistic;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class JobFilterStatisticRepository
{
    public const TABLE = 'tx_jobs_filter_statistic';

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
    ) {}

    public function log(JobDemand $demand, int $resultCount, string $sessionHash, int $language, int $timestamp): void
    {
        $this->connection()->insert(self::TABLE, [
            'session_hash' => $sessionHash,
            'filter_hash' => self::hashDemand($demand),
            'level' => $demand->getLevel(),
            'occupational_field' => $demand->getOccupationalField(),
            'location' => $demand->getLocation(),
            'employment_type' => $demand->getEmploymentType(),
            'remote_only' => (int)$demand->isRemoteOnly(),
            'search' => mb_substr($demand->getSearch(), 0, 255),
            'result_count' => $resultCount,
            'language' => $language,
            'crdate' => $timestamp,
            'tstamp' => $timestamp,
        ]);
    }

    /**
     * Identical filter combinations share one hash, which is what the
     * statistics module groups by. The search term is compared case-insensitively
     * so "typo3" and "TYPO3" end up in the same row.
     */
    public static function hashDemand(JobDemand $demand): string
    {
        return sha1(json_encode([
            $demand->getLevel(),
            $demand->getOccupationalField(),
            $demand->getLocation(),
            $demand->getEmploymentType(),
            $demand->isRemoteOnly(),
            mb_strtolower($demand->getSearch()),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * Every distinct filter combination with its usage count, most used first.
     *
     * @return list<array{filter_hash: string, level: int, occupational_field: int, location: int, employment_type: string, remote_only: bool, search: string, count: int, average_results: float, last_used: int}>
     */
    public function findAggregated(): array
    {
        $queryBuilder = $this->connection()->createQueryBuilder();
        // Every grouped column is listed so strict GROUP BY modes accept the query.
        $rows = $queryBuilder
            ->select('filter_hash', 'level', 'occupational_field', 'location', 'employment_type', 'remote_only')
            ->addSelectLiteral(
                'MIN(' . $queryBuilder->quoteIdentifier('search') . ') AS ' . $queryBuilder->quoteIdentifier('search'),
                'COUNT(*) AS ' . $queryBuilder->quoteIdentifier('usage_count'),
                'AVG(' . $queryBuilder->quoteIdentifier('result_count') . ') AS ' . $queryBuilder->quoteIdentifier('average_results'),
                'MAX(' . $queryBuilder->quoteIdentifier('crdate') . ') AS ' . $queryBuilder->quoteIdentifier('last_used'),
            )
            ->from(self::TABLE)
            ->groupBy('filter_hash', 'level', 'occupational_field', 'location', 'employment_type', 'remote_only')
            ->orderBy('usage_count', 'DESC')
            ->addOrderBy('last_used', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(static fn(array $row): array => [
            'filter_hash' => (string)$row['filter_hash'],
            'level' => (int)$row['level'],
            'occupational_field' => (int)$row['occupational_field'],
            'location' => (int)$row['location'],
            'employment_type' => (string)$row['employment_type'],
            'remote_only' => (bool)$row['remote_only'],
            'search' => (string)$row['search'],
            'count' => (int)$row['usage_count'],
            'average_results' => round((float)$row['average_results'], 1),
            'last_used' => (int)$row['last_used'],
        ], $rows);
    }

    /**
     * @return list<JobFilterStatistic>
     */
    public function findByFilterHash(string $filterHash): array
    {
        $queryBuilder = $this->connection()->createQueryBuilder();
        $rows = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->eq('filter_hash', $queryBuilder->createNamedParameter($filterHash)))
            ->orderBy('crdate', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(JobFilterStatistic::fromRow(...), $rows);
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
