<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Core\Environment;

defined('TYPO3') or die();

// Development instance only: allow the trivial "test" password so the login can
// be written down in the documentation. This package is a require-dev
// dependency and is marked export-ignore, so it can never reach a production
// deployment — the context check is the second lock on the same door.
if (Environment::getContext()->isDevelopment()) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['jobsTestSite'] = [
        'generator' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['default']['generator'],
        'validators' => [],
    ];
    $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy'] = 'jobsTestSite';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy'] = 'jobsTestSite';
}
