<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Service\Backend;

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;

/**
 * Link to the place where the tracking switch lives: Admin Tools > Settings,
 * which opens the extension configuration. The module belongs to EXT:install
 * and is only reachable for system maintainers, so the link may be empty.
 */
class ExtensionConfigurationLinkProvider
{
    public function __construct(
        protected readonly UriBuilder $uriBuilder,
    ) {}

    public function getUrl(): string
    {
        try {
            return (string)$this->uriBuilder->buildUriFromRoute('system_settings');
        } catch (RouteNotFoundException) {
            return '';
        }
    }
}
