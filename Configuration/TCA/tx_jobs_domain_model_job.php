<?php

declare(strict_types=1);

use BastianSchwabe\Jobs\Enum\EducationCategory;
use BastianSchwabe\Jobs\Enum\EmploymentType;
use BastianSchwabe\Jobs\Enum\SalaryUnit;
use BastianSchwabe\Jobs\Tca\CountryItemsProvider;

return [
    'ctrl' => [
        'title' => 'jobs.db:tx_jobs_domain_model_job',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'date_posted DESC',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'iconfile' => 'EXT:jobs/Resources/Public/Icons/tx_jobs_domain_model_job.svg',
    ],
    'palettes' => [
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
        'timeRestriction' => ['showitem' => 'starttime, endtime'],
        'classification' => ['showitem' => 'level, occupational_field'],
        'dates' => ['showitem' => 'date_posted, valid_through'],
        'remote' => ['showitem' => 'job_location_type, --linebreak--, applicant_location_requirements'],
        'experience' => ['showitem' => 'experience_months, experience_in_place_of_education'],
        'education' => ['showitem' => 'education_category, --linebreak--, education_requirements'],
        'salary' => [
            'showitem' => 'base_salary_currency, base_salary_unit, --linebreak--,'
                . ' base_salary_value, base_salary_min, base_salary_max',
        ],
        'application' => ['showitem' => 'direct_apply, --linebreak--, application_url, application_email'],
    ],
    'columns' => [
        'title' => [
            'label' => 'jobs.db:tx_jobs_domain_model_job.title',
            'description' => 'jobs.db:tx_jobs_domain_model_job.title.description',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'max' => 255,
            ],
        ],
        'slug' => [
            'label' => 'jobs.db:tx_jobs_domain_model_job.slug',
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
        'teaser' => [
            'label' => 'jobs.db:tx_jobs_domain_model_job.teaser',
            'description' => 'jobs.db:tx_jobs_domain_model_job.teaser.description',
            'config' => ['type' => 'text', 'rows' => 3, 'cols' => 40, 'max' => 500],
        ],
        'description' => [
            'label' => 'jobs.db:tx_jobs_domain_model_job.description',
            'description' => 'jobs.db:tx_jobs_domain_model_job.description.description',
            'config' => [
                'type' => 'text',
                'required' => true,
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'rows' => 15,
                'cols' => 40,
            ],
        ],
        'level' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.level',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_jobs_domain_model_level',
                'foreign_table_where' => 'ORDER BY tx_jobs_domain_model_level.sorting',
                'items' => [['label' => '', 'value' => 0]],
                'default' => 0,
            ],
        ],
        'occupational_field' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.occupational_field',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_jobs_domain_model_occupational_field',
                'foreign_table_where' => 'ORDER BY tx_jobs_domain_model_occupational_field.sorting',
                'items' => [['label' => '', 'value' => 0]],
                'default' => 0,
            ],
        ],
        'locations' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.locations',
            'description' => 'jobs.db:tx_jobs_domain_model_job.locations.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_jobs_domain_model_location',
                'foreign_table_where' => 'ORDER BY tx_jobs_domain_model_location.sorting',
                'MM' => 'tx_jobs_job_location_mm',
                'size' => 5,
                'maxitems' => 20,
            ],
        ],
        'contacts' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.contacts',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_jobs_domain_model_contact',
                'foreign_table_where' => 'ORDER BY tx_jobs_domain_model_contact.sorting',
                'MM' => 'tx_jobs_job_contact_mm',
                'size' => 5,
                'maxitems' => 20,
            ],
        ],
        'company_profiles' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.company_profiles',
            'description' => 'jobs.db:tx_jobs_domain_model_job.company_profiles.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_jobs_domain_model_company_profile',
                'foreign_table_where' => 'ORDER BY tx_jobs_domain_model_company_profile.sorting',
                'MM' => 'tx_jobs_job_company_profile_mm',
                'size' => 3,
                'maxitems' => 10,
            ],
        ],
        'images' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.images',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 20,
            ],
        ],
        'downloads' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.downloads',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-text-types,pdf',
                'maxitems' => 20,
            ],
        ],
        'date_posted' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.date_posted',
            'description' => 'jobs.db:tx_jobs_domain_model_job.date_posted.description',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'required' => true,
                'default' => 0,
            ],
        ],
        'valid_through' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.valid_through',
            'description' => 'jobs.db:tx_jobs_domain_model_job.valid_through.description',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'nullable' => true,
            ],
        ],
        'employment_type' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.employment_type',
            'description' => 'jobs.db:tx_jobs_domain_model_job.employment_type.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => EmploymentType::tcaItems(),
                'maxitems' => 8,
                'default' => EmploymentType::FullTime->value,
            ],
        ],
        'education_category' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.education_category',
            'description' => 'jobs.db:tx_jobs_domain_model_job.education_category.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => EducationCategory::tcaItems(),
                'default' => '',
            ],
        ],
        'education_requirements' => [
            'label' => 'jobs.db:tx_jobs_domain_model_job.education_requirements',
            'config' => ['type' => 'text', 'rows' => 4, 'cols' => 40],
        ],
        'experience_months' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.experience_months',
            'description' => 'jobs.db:tx_jobs_domain_model_job.experience_months.description',
            'config' => [
                'type' => 'number',
                'size' => 10,
                'default' => 0,
                'range' => ['lower' => 0, 'upper' => 600],
            ],
        ],
        'experience_in_place_of_education' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.experience_in_place_of_education',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'experience_requirements' => [
            'label' => 'jobs.db:tx_jobs_domain_model_job.experience_requirements',
            'config' => ['type' => 'text', 'rows' => 4, 'cols' => 40],
        ],
        'industry' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.industry',
            'description' => 'jobs.db:tx_jobs_domain_model_job.industry.description',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 255],
        ],
        'occupational_category' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.occupational_category',
            'description' => 'jobs.db:tx_jobs_domain_model_job.occupational_category.description',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 255],
        ],
        'identifier' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.identifier',
            'description' => 'jobs.db:tx_jobs_domain_model_job.identifier.description',
            'config' => ['type' => 'input', 'eval' => 'trim', 'max' => 255],
        ],
        'job_location_type' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.job_location_type',
            'description' => 'jobs.db:tx_jobs_domain_model_job.job_location_type.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'applicant_location_requirements' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.applicant_location_requirements',
            'description' => 'jobs.db:tx_jobs_domain_model_job.applicant_location_requirements.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [],
                'itemsProcFunc' => CountryItemsProvider::class . '->items',
                'size' => 5,
                'maxitems' => 50,
            ],
        ],
        'direct_apply' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.direct_apply',
            'description' => 'jobs.db:tx_jobs_domain_model_job.direct_apply.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'application_url' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.application_url',
            'config' => ['type' => 'link', 'allowedTypes' => ['page', 'url']],
        ],
        'application_email' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.application_email',
            'config' => ['type' => 'email'],
        ],
        'base_salary_currency' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.base_salary_currency',
            'description' => 'jobs.db:tx_jobs_domain_model_job.base_salary_currency.description',
            'config' => ['type' => 'input', 'eval' => 'trim,upper', 'size' => 6, 'max' => 3],
        ],
        'base_salary_unit' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.base_salary_unit',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => SalaryUnit::tcaItems(),
                'default' => '',
            ],
        ],
        'base_salary_value' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.base_salary_value',
            'description' => 'jobs.db:tx_jobs_domain_model_job.base_salary_value.description',
            'config' => ['type' => 'number', 'format' => 'decimal', 'size' => 12, 'range' => ['lower' => 0]],
        ],
        'base_salary_min' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.base_salary_min',
            'config' => ['type' => 'number', 'format' => 'decimal', 'size' => 12, 'range' => ['lower' => 0]],
        ],
        'base_salary_max' => [
            'l10n_mode' => 'exclude',
            'label' => 'jobs.db:tx_jobs_domain_model_job.base_salary_max',
            'config' => ['type' => 'number', 'format' => 'decimal', 'size' => 12, 'range' => ['lower' => 0]],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    title, slug, teaser, description,
                    --palette--;jobs.db:palette.classification;classification,
                    --palette--;jobs.db:palette.dates;dates,
                --div--;jobs.db:tab.relations,
                    locations,
                    --palette--;jobs.db:palette.remote;remote,
                    company_profiles, contacts,
                --div--;jobs.db:tab.media,
                    images, downloads,
                --div--;jobs.db:tab.requirements,
                    employment_type,
                    --palette--;jobs.db:palette.education;education,
                    --palette--;jobs.db:palette.experience;experience,
                    experience_requirements,
                --div--;jobs.db:tab.schema,
                    identifier, industry, occupational_category,
                    --palette--;jobs.db:palette.salary;salary,
                    --palette--;jobs.db:palette.application;application,
                --div--;core.form.tabs:language,
                    --palette--;;language,
                --div--;core.form.tabs:access,
                    hidden, --palette--;;timeRestriction,
            ',
        ],
    ],
];
