<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Repository;

use BastianSchwabe\Jobs\Domain\Dto\JobDemand;
use BastianSchwabe\Jobs\Domain\Model\Job;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Job>
 */
class JobRepository extends Repository
{
    protected $defaultOrderings = [
        'datePosted' => QueryInterface::ORDER_DESCENDING,
    ];

    /**
     * @return QueryResultInterface<int, Job>
     */
    public function findByDemand(JobDemand $demand, ?\DateTimeImmutable $now = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = [$this->createNotExpiredConstraint($query, $now ?? new \DateTimeImmutable())];

        if ($demand->getLevel() > 0) {
            $constraints[] = $query->equals('level', $demand->getLevel());
        }

        if ($demand->getOccupationalField() > 0) {
            $constraints[] = $query->equals('occupationalField', $demand->getOccupationalField());
        }

        if ($demand->getLocation() > 0) {
            $constraints[] = $query->contains('locations', $demand->getLocation());
        }

        if ($demand->getEmploymentType() !== '') {
            // The column holds a comma separated list, so a substring match is
            // the only option here. Values are enum-backed and unambiguous.
            $constraints[] = $query->like('employmentType', '%' . $demand->getEmploymentType() . '%');
        }

        if ($demand->isRemoteOnly()) {
            $constraints[] = $query->equals('jobLocationType', true);
        }

        if ($demand->getSearch() !== '') {
            $searchTerm = '%' . $demand->getSearch() . '%';
            $constraints[] = $query->logicalOr(
                $query->like('title', $searchTerm),
                $query->like('teaser', $searchTerm),
                $query->like('description', $searchTerm),
            );
        }

        $query->matching($query->logicalAnd(...$constraints));

        return $query->execute();
    }

    /**
     * A job without an expiry date never expires; otherwise it stays visible up
     * to and including its validThrough date.
     */
    /**
     * @param QueryInterface<Job> $query
     */
    private function createNotExpiredConstraint(QueryInterface $query, \DateTimeImmutable $now): ConstraintInterface
    {
        return $query->logicalOr(
            $query->equals('validThrough', null),
            $query->greaterThanOrEqual('validThrough', $now->setTime(0, 0)),
        );
    }
}
