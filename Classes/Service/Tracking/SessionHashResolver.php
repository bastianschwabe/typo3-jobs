<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Service\Tracking;

use BastianSchwabe\Jobs\Configuration\TrackingSettings;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Turns the current frontend request into an opaque session key.
 *
 * The statistics count "one visitor" as "one frontend session". TYPO3 does not
 * persist an anonymous session until it carries data, and an unpersisted
 * session gets a new identifier on every request. Writing a marker into the
 * session makes the authentication middleware fixate it and set the fe_typo_user
 * cookie at the end of the request, so the next request arrives with the same
 * identifier. Only a hash of that identifier is stored.
 */
class SessionHashResolver
{
    private const SESSION_KEY = 'tx_jobs_tracking';

    public function __construct(
        protected readonly Context $context,
        protected readonly TrackingSettings $settings,
    ) {}

    /**
     * Null when the request should not be tracked at all: tracking is switched
     * off in the extension configuration, there is no frontend user object,
     * or a backend user is logged in and probably previewing.
     *
     * Every frontend entry point goes through here, so the switch also keeps
     * the session marker and therefore the cookie away from visitors.
     */
    public function resolve(ServerRequestInterface $request): ?string
    {
        if (!$this->settings->isEnabled()) {
            return null;
        }

        $frontendUser = $request->getAttribute('frontend.user');
        if (!$frontendUser instanceof FrontendUserAuthentication) {
            return null;
        }

        if ($this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)) {
            return null;
        }

        if ($frontendUser->getSessionData(self::SESSION_KEY) === null) {
            $frontendUser->setSessionData(self::SESSION_KEY, [
                'since' => $this->context->getPropertyFromAspect('date', 'timestamp'),
            ]);
        }

        return hash('sha256', $frontendUser->getSession()->getIdentifier());
    }
}
