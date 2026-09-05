<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Repository;

use BastianSchwabe\Jobs\Domain\Model\OccupationalField;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<OccupationalField>
 */
class OccupationalFieldRepository extends Repository
{
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING,
    ];
}
