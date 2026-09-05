<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

// registerPlugin() creates the CType, its icon and the FlexForm wiring in one
// call and returns the plugin signature ("jobs_list" / "jobs_show").
ExtensionUtility::registerPlugin(
    'Jobs',
    'List',
    'jobs.db:plugin.list.title',
    'jobs-plugin-list',
    'plugins',
    'jobs.db:plugin.list.description',
    'FILE:EXT:jobs/Configuration/FlexForms/List.xml',
);

ExtensionUtility::registerPlugin(
    'Jobs',
    'Show',
    'jobs.db:plugin.show.title',
    'jobs-plugin-show',
    'plugins',
    'jobs.db:plugin.show.description',
    'FILE:EXT:jobs/Configuration/FlexForms/Show.xml',
);
