<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Service\Tracking;

use BastianSchwabe\Jobs\Domain\Dto\JobDemand;
use BastianSchwabe\Jobs\Domain\Repository\JobApplyStatisticRepository;
use BastianSchwabe\Jobs\Domain\Repository\JobFilterStatisticRepository;
use BastianSchwabe\Jobs\Domain\Repository\JobViewStatisticRepository;
use BastianSchwabe\Jobs\Enum\ApplyStatus;
use BastianSchwabe\Jobs\Enum\ApplyType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Single entry point for everything the frontend records. Callers hand in a
 * session hash from SessionHashResolver; the service never touches the request.
 */
class TrackingService
{
    public function __construct(
        protected readonly JobViewStatisticRepository $viewRepository,
        protected readonly JobApplyStatisticRepository $applyRepository,
        protected readonly JobFilterStatisticRepository $filterRepository,
        protected readonly ConnectionPool $connectionPool,
        protected readonly Context $context,
    ) {}

    public function trackView(int $jobUid, string $sessionHash): void
    {
        $this->viewRepository->track($jobUid, $sessionHash, $this->now());
    }

    public function trackApply(int $jobUid, string $sessionHash, ApplyType $type, ApplyStatus $status = ApplyStatus::Started): void
    {
        $this->applyRepository->log($jobUid, $sessionHash, $type, $status, $this->now());
    }

    public function trackFilter(JobDemand $demand, int $resultCount, string $sessionHash, int $language = 0): void
    {
        $this->filterRepository->log($demand, $resultCount, $sessionHash, $language, $this->now());
    }

    /**
     * Statistics are always attached to the default-language record, so a
     * translated job and its original count as one. Returns null for a job
     * the frontend would not show either.
     */
    public function resolveTrackedJobUid(int $uid): ?int
    {
        if ($uid <= 0) {
            return null;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jobs_domain_model_job');
        $row = $queryBuilder
            ->select('uid', 'l10n_parent')
            ->from('tx_jobs_domain_model_job')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return (int)$row['l10n_parent'] > 0 ? (int)$row['l10n_parent'] : (int)$row['uid'];
    }

    /** Empties all three tracking tables. */
    public function resetAll(): void
    {
        $this->viewRepository->truncate();
        $this->applyRepository->truncate();
        $this->filterRepository->truncate();
    }

    private function now(): int
    {
        return (int)$this->context->getPropertyFromAspect('date', 'timestamp');
    }
}
