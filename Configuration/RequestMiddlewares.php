<?php

declare(strict_types=1);

use BastianSchwabe\Jobs\Middleware\JobViewTracker;

// After the page argument validator the route enhancer has decoded the plugin
// arguments and the cHash is verified; before TSFE preparation the request is
// still cheap to inspect. The frontend user is already authenticated at this
// point, which the session-based counter needs.
return [
    'frontend' => [
        'bastianschwabe/jobs/job-view-tracker' => [
            'target' => JobViewTracker::class,
            'after' => [
                'typo3/cms-frontend/page-argument-validator',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
    ],
];
