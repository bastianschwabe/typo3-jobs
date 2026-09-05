<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'jobs.db:tx_jobs_domain_model_company_profile',
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
        'iconfile' => 'EXT:jobs/Resources/Public/Icons/tx_jobs_domain_model_company_profile.svg',
    ],
    'palettes' => [
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
        'urls' => ['showitem' => 'url_website, --linebreak--, url_linkedin, url_xing'],
        'organization' => ['showitem' => 'legal_name, --linebreak--, founding_date, number_of_employees'],
    ],
    'columns' => [
        'title' => [
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.title',
            'description' => 'jobs.db:tx_jobs_domain_model_company_profile.title.description',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'max' => 255,
            ],
        ],
        'teaser' => [
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.teaser',
            'config' => ['type' => 'text', 'rows' => 3, 'cols' => 40, 'max' => 500],
        ],
        'description' => [
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'rows' => 10,
                'cols' => 40,
            ],
        ],
        'images' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.images',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 20,
            ],
        ],
        'logo' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.logo',
            'description' => 'jobs.db:tx_jobs_domain_model_company_profile.logo.description',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 1,
            ],
        ],
        'url_website' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.url_website',
            'config' => ['type' => 'link', 'allowedTypes' => ['page', 'url']],
        ],
        'url_linkedin' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.url_linkedin',
            'config' => ['type' => 'link', 'allowedTypes' => ['url']],
        ],
        'url_xing' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.url_xing',
            'config' => ['type' => 'link', 'allowedTypes' => ['url']],
        ],
        'location' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.location',
            'description' => 'jobs.db:tx_jobs_domain_model_company_profile.location.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_jobs_domain_model_location',
                'foreign_table_where' => 'ORDER BY tx_jobs_domain_model_location.sorting',
                'items' => [['label' => '', 'value' => 0]],
                'default' => 0,
            ],
        ],
        'legal_name' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.legal_name',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 255],
        ],
        'founding_date' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.founding_date',
            'config' => ['type' => 'datetime', 'format' => 'date', 'nullable' => true],
        ],
        'number_of_employees' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_company_profile.number_of_employees',
            'config' => ['type' => 'number', 'size' => 10, 'default' => 0, 'range' => ['lower' => 0]],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    title, teaser, description,
                --div--;jobs.db:tab.media,
                    logo, images,
                --div--;jobs.db:tab.contact,
                    location,
                    --palette--;jobs.db:palette.urls;urls,
                --div--;jobs.db:tab.schema,
                    --palette--;jobs.db:palette.organization;organization,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    hidden,
            ',
        ],
    ],
];
