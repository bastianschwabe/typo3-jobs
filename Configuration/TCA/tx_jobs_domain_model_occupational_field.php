<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'jobs.db:tx_jobs_domain_model_occupational_field',
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
        'iconfile' => 'EXT:jobs/Resources/Public/Icons/tx_jobs_domain_model_occupational_field.svg',
    ],
    'palettes' => [
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
    ],
    'columns' => [
        'title' => [
            'label' => 'jobs.db:tx_jobs_domain_model_occupational_field.title',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'max' => 255,
            ],
        ],
        'slug' => [
            'label' => 'jobs.db:tx_jobs_domain_model_occupational_field.slug',
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
            'label' => 'jobs.db:tx_jobs_domain_model_occupational_field.description',
            'config' => [
                'type' => 'text',
                'rows' => 4,
                'cols' => 40,
            ],
        ],
        'occupational_category' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_occupational_field.occupational_category',
            'description' => 'jobs.db:tx_jobs_domain_model_occupational_field.occupational_category.description',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'max' => 255,
            ],
        ],
        'industry' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_occupational_field.industry',
            'description' => 'jobs.db:tx_jobs_domain_model_occupational_field.industry.description',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'max' => 255,
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    title, slug, description,
                --div--;jobs.db:tab.schema,
                    occupational_category, industry,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    hidden,
            ',
        ],
    ],
];
