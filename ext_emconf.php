<?php

declare(strict_types=1);

// Kept only for TER and Tailor. Since v14.3 TYPO3 itself no longer reads this
// file, because composer.json carries "extra.typo3/cms.version" and
// "providesPackages". Keep the version here in sync with composer.json.
$EM_CONF[$_EXTKEY] = [
    'title' => 'Jobs',
    'description' => 'Job postings with filterable list, detail view and schema.org/JobPosting JSON-LD output.',
    'category' => 'plugin',
    'author' => 'Bastian Schwabe',
    'author_company' => 'neuedaten',
    'state' => 'beta',
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-0.0.0',
            'typo3' => '14.3.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'dashboard' => '14.3.0-14.99.99',
        ],
    ],
];
