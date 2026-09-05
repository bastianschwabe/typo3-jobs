<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'jobs.db:tx_jobs_domain_model_location',
        'label' => 'name',
        'label_alt' => 'city',
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
        'iconfile' => 'EXT:jobs/Resources/Public/Icons/tx_jobs_domain_model_location.svg',
    ],
    'palettes' => [
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
        'street' => ['showitem' => 'street, address_addition'],
        'city' => ['showitem' => 'zip, city'],
        'region' => ['showitem' => 'address_region, address_country'],
        'geo' => ['showitem' => 'latitude, longitude'],
        'contact' => ['showitem' => 'phone, email'],
        'urls' => ['showitem' => 'url_website, --linebreak--, url_linkedin, url_xing'],
    ],
    'columns' => [
        'name' => [
            'label' => 'jobs.db:tx_jobs_domain_model_location.name',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'max' => 255,
            ],
        ],
        'street' => [
            'label' => 'jobs.db:tx_jobs_domain_model_location.street',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 255],
        ],
        'address_addition' => [
            'label' => 'jobs.db:tx_jobs_domain_model_location.address_addition',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 255],
        ],
        'zip' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_location.zip',
            'config' => ['type' => 'input', 'eval' => 'trim', 'size' => 12, 'max' => 20],
        ],
        'city' => [
            'label' => 'jobs.db:tx_jobs_domain_model_location.city',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 255],
        ],
        'address_region' => [
            'label' => 'jobs.db:tx_jobs_domain_model_location.address_region',
            'description' => 'jobs.db:tx_jobs_domain_model_location.address_region.description',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 255],
        ],
        'address_country' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_location.address_country',
            'description' => 'jobs.db:tx_jobs_domain_model_location.address_country.description',
            'config' => [
                'type' => 'country',
                'labelField' => 'localizedName',
                'default' => 'DE',
            ],
        ],
        // Deliberately not TCA type "number" with format "decimal":
        // DataHandler rounds those to two decimals, which is roughly a
        // kilometre of error. See ext_tables.sql for the matching column.
        'latitude' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_location.latitude',
            'description' => 'jobs.db:tx_jobs_domain_model_location.latitude.description',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 15,
                'max' => 20,
                'placeholder' => '52.52000000',
            ],
        ],
        'longitude' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_location.longitude',
            'description' => 'jobs.db:tx_jobs_domain_model_location.longitude.description',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 15,
                'max' => 20,
                'placeholder' => '13.40500000',
            ],
        ],
        'phone' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_location.phone',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 60],
        ],
        'email' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_location.email',
            'config' => ['type' => 'email'],
        ],
        'url_website' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_location.url_website',
            'config' => ['type' => 'link', 'allowedTypes' => ['page', 'url']],
        ],
        'url_linkedin' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_location.url_linkedin',
            'config' => ['type' => 'link', 'allowedTypes' => ['url']],
        ],
        'url_xing' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_location.url_xing',
            'config' => ['type' => 'link', 'allowedTypes' => ['url']],
        ],
        'photo' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_location.photo',
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
                    name,
                    --palette--;jobs.db:palette.address;street,
                    --palette--;;city,
                    --palette--;;region,
                    --palette--;jobs.db:palette.geo;geo,
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
