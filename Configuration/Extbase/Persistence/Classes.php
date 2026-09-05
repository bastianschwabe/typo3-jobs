<?php

declare(strict_types=1);

use BastianSchwabe\Jobs\Domain\Model\CompanyProfile;
use BastianSchwabe\Jobs\Domain\Model\OccupationalField;

// Extbase derives a table name by lowercasing the class name without inserting
// underscores, which would give "…_occupationalfield". These two multi-word
// models are mapped explicitly so the table names stay readable.
return [
    OccupationalField::class => [
        'tableName' => 'tx_jobs_domain_model_occupational_field',
    ],
    CompanyProfile::class => [
        'tableName' => 'tx_jobs_domain_model_company_profile',
    ],
];
