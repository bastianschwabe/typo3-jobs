<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Repository;

use BastianSchwabe\Jobs\Domain\Model\Contact;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Contact>
 */
class ContactRepository extends Repository
{
    protected $defaultOrderings = [
        'lastName' => QueryInterface::ORDER_ASCENDING,
    ];
}
