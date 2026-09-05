<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'jobs-plugin-list' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:jobs/Resources/Public/Icons/plugin-list.svg',
    ],
    'jobs-plugin-show' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:jobs/Resources/Public/Icons/plugin-show.svg',
    ],
    'jobs-module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:jobs/Resources/Public/Icons/module-jobs.svg',
    ],
    'jobs-module-list' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:jobs/Resources/Public/Icons/module-list.svg',
    ],
    'jobs-module-statistics' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:jobs/Resources/Public/Icons/module-statistics.svg',
    ],
];
