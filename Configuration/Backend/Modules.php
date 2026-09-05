<?php

declare(strict_types=1);

use BastianSchwabe\Jobs\Controller\Backend\JobModuleController;
use BastianSchwabe\Jobs\Controller\Backend\StatisticModuleController;

/**
 * Module group "Jobs" with two entries. Neither module needs a page tree:
 * both read every job in the installation.
 */
return [
    'jobs' => [
        'position' => ['after' => 'content'],
        'workspaces' => 'live',
        'iconIdentifier' => 'jobs-module',
        'labels' => 'jobs.modules.jobs',
    ],
    'jobs_list' => [
        'parent' => 'jobs',
        'position' => ['before' => '*'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/jobs/list',
        'iconIdentifier' => 'jobs-module-list',
        'labels' => 'jobs.modules.list',
        'routes' => [
            '_default' => [
                'target' => JobModuleController::class . '::handleRequest',
            ],
        ],
        // Persisted per user, so the filter survives leaving the module.
        'moduleData' => [
            'filter' => 'all',
        ],
    ],
    'jobs_statistics' => [
        'parent' => 'jobs',
        'position' => ['after' => 'jobs_list'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/jobs/statistics',
        'iconIdentifier' => 'jobs-module-statistics',
        'labels' => 'jobs.modules.statistics',
        'routes' => [
            '_default' => [
                'target' => StatisticModuleController::class . '::jobsAction',
            ],
            'filter' => [
                'target' => StatisticModuleController::class . '::filterAction',
            ],
            'functions' => [
                'target' => StatisticModuleController::class . '::functionsAction',
            ],
            'reset' => [
                'target' => StatisticModuleController::class . '::resetAction',
                'methods' => ['POST'],
            ],
        ],
    ],
];
