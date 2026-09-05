<?php

declare(strict_types=1);

use BastianSchwabe\Jobs\Controller\JobController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

// Since v14 every plugin is its own CType; the fifth argument of
// configurePlugin() is gone. registerPlugin() lives in TCA/Overrides/tt_content.php.
// The list action is registered as non-cacheable: its filter form submits plain
// GET parameters, and TYPO3 rejects cache-relevant parameters that carry no
// cHash. A GET form cannot produce one, and keeping the filter in the URL is
// worth more here than caching the list.
ExtensionUtility::configurePlugin(
    'Jobs',
    'List',
    [JobController::class => 'list'],
    [JobController::class => 'list'],
);

// "apply" is the tracking proxy in front of the application link. It has to
// run on every click, so it is non-cacheable; the detail view itself stays
// cached. Extbase switches the plugin to USER_INT only for that action.
ExtensionUtility::configurePlugin(
    'Jobs',
    'Show',
    [JobController::class => 'show, apply'],
    [JobController::class => 'apply'],
);

// The list plugin renders as USER_INT, so its GET parameters must not take part
// in the cache hash: the page shell stays cached while the plugin is rendered
// per request. Without this, TYPO3 rejects every filter URL with "cHash empty".
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^tx_jobs_list[';

// Global Fluid namespace so templates and overrides can use <jobs:...>
// without repeating the namespace declaration.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['jobs'][]
    = 'BastianSchwabe\\Jobs\\ViewHelpers';
