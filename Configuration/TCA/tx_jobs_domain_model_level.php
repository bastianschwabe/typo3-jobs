<?php

declare(strict_types=1);

// System columns (hidden, sys_language_uid, l10n_parent, ...) are derived from
// "ctrl" by the Core since v13 and are therefore not repeated in "columns".
return [
    'ctrl' => [
        'title' => 'jobs.db:tx_jobs_domain_model_level',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:jobs/Resources/Public/Icons/tx_jobs_domain_model_level.svg',
    ],
    'palettes' => [
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
    ],
    'columns' => [
        'title' => [
            'label' => 'jobs.db:tx_jobs_domain_model_level.title',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'max' => 255,
            ],
        ],
        'slug' => [
            'label' => 'jobs.db:tx_jobs_domain_model_level.slug',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['title'],
                    'replacements' => ['/' => '-'],
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
                'default' => '',
            ],
        ],
        'description' => [
            'label' => 'jobs.db:tx_jobs_domain_model_level.description',
            'config' => [
                'type' => 'text',
                'rows' => 4,
                'cols' => 40,
            ],
        ],
        'months_of_experience' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_level.months_of_experience',
            'description' => 'jobs.db:tx_jobs_domain_model_level.months_of_experience.description',
            'config' => [
                'type' => 'number',
                'size' => 10,
                'default' => 0,
                'range' => ['lower' => 0, 'upper' => 600],
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    title, slug, description, months_of_experience,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    hidden,
            ',
        ],
    ],
];
