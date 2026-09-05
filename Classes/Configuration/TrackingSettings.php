<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Configuration;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * The extension configuration switch for the whole tracking feature
 * (Admin Tools > Settings > Extension Configuration > jobs).
 *
 * Enabled by default: the option only exists in TYPO3_CONF_VARS once the
 * configuration was saved or synchronised, and a fresh installation should
 * behave like the ext_conf_template default.
 */
class TrackingSettings
{
    public function __construct(
        protected readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function isEnabled(): bool
    {
        try {
            $value = $this->extensionConfiguration->get('jobs', 'tracking/enabled');
        } catch (\Throwable) {
            return true;
        }

        return (bool)$value;
    }
}
