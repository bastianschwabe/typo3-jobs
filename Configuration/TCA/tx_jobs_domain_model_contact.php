<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'jobs.db:tx_jobs_domain_model_contact',
        'label' => 'last_name',
        'label_alt' => 'first_name',
        'label_alt_force' => true,
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
        'iconfile' => 'EXT:jobs/Resources/Public/Icons/tx_jobs_domain_model_contact.svg',
    ],
    'palettes' => [
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
        'name' => ['showitem' => 'first_name, last_name'],
        'contact' => ['showitem' => 'phone, email'],
        'urls' => ['showitem' => 'url_website, --linebreak--, url_linkedin, url_xing'],
    ],
    'columns' => [
        'first_name' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_contact.first_name',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 255],
        ],
        'last_name' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_contact.last_name',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'max' => 255,
            ],
        ],
        'role' => [
            'label' => 'jobs.db:tx_jobs_domain_model_contact.role',
            'description' => 'jobs.db:tx_jobs_domain_model_contact.role.description',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 255],
        ],
        'description' => [
            'label' => 'jobs.db:tx_jobs_domain_model_contact.description',
            'config' => ['type' => 'text', 'rows' => 5, 'cols' => 40],
        ],
        'phone' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_contact.phone',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 60],
        ],
        'email' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_contact.email',
            'config' => ['type' => 'email'],
        ],
        'url_website' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_contact.url_website',
            'config' => ['type' => 'link', 'allowedTypes' => ['page', 'url']],
        ],
        'url_linkedin' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_contact.url_linkedin',
            'config' => ['type' => 'link', 'allowedTypes' => ['url']],
        ],
        'url_xing' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_contact.url_xing',
            'config' => ['type' => 'link', 'allowedTypes' => ['url']],
        ],
        'photo' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_contact.photo',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 1,
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    --palette--;jobs.db:palette.name;name,
                    role, description,
                --div--;jobs.db:tab.contact,
                    --palette--;;contact,
                    --palette--;jobs.db:palette.urls;urls,
                --div--;jobs.db:tab.media,
                    photo,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    hidden,
            ',
        ],
    ],
];
