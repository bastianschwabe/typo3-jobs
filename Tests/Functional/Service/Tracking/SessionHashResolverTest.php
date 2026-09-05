<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\Service\Tracking;

use BastianSchwabe\Jobs\Service\Tracking\SessionHashResolver;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SessionHashResolverTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    #[Test]
    public function requestsWithoutFrontendUserAreNotTracked(): void
    {
        self::assertNull($this->get(SessionHashResolver::class)->resolve(new ServerRequest('https://example.org/')));
    }

    #[Test]
    public function hashIsStableWithinOneSessionAndNeverTheRawIdentifier(): void
    {
        $request = $this->createRequestWithFrontendUser();
        $resolver = $this->get(SessionHashResolver::class);

        $hash = $resolver->resolve($request);

        self::assertNotNull($hash);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
        self::assertSame($hash, $resolver->resolve($request));

        $frontendUser = $request->getAttribute('frontend.user');
        self::assertInstanceOf(FrontendUserAuthentication::class, $frontendUser);
        self::assertStringNotContainsString($frontendUser->getSession()->getIdentifier(), $hash);
    }

    /**
     * An anonymous session is only persisted once it carries data. The marker
     * is what makes TYPO3 send the cookie, so it has to be there.
     */
    #[Test]
    public function marksTheSessionSoItGetsPersisted(): void
    {
        $request = $this->createRequestWithFrontendUser();
        $frontendUser = $request->getAttribute('frontend.user');
        self::assertInstanceOf(FrontendUserAuthentication::class, $frontendUser);

        self::assertNull($frontendUser->getSessionData('tx_jobs_tracking'));

        $this->get(SessionHashResolver::class)->resolve($request);

        self::assertIsArray($frontendUser->getSessionData('tx_jobs_tracking'));
        self::assertTrue($frontendUser->getSession()->dataWasUpdated());
    }

    /**
     * The extension configuration switch has to stop everything, including the
     * session marker: a visitor of a site with tracking off gets no cookie.
     */
    #[Test]
    public function disabledTrackingResolvesNothingAndLeavesTheSessionAlone(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['jobs']['tracking']['enabled'] = '0';
        $request = $this->createRequestWithFrontendUser();

        self::assertNull($this->get(SessionHashResolver::class)->resolve($request));

        $frontendUser = $request->getAttribute('frontend.user');
        self::assertInstanceOf(FrontendUserAuthentication::class, $frontendUser);
        self::assertNull($frontendUser->getSessionData('tx_jobs_tracking'));
    }

    #[Test]
    public function missingConfigurationMeansEnabled(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['jobs']);

        self::assertNotNull($this->get(SessionHashResolver::class)->resolve($this->createRequestWithFrontendUser()));
    }

    #[Test]
    public function loggedInBackendUsersAreNotTracked(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->get(Context::class)->setAspect('backend.user', new UserAspect($this->setUpBackendUser(1)));

        self::assertNull($this->get(SessionHashResolver::class)->resolve($this->createRequestWithFrontendUser()));
    }

    private function createRequestWithFrontendUser(): ServerRequest
    {
        $request = new ServerRequest('https://example.org/');
        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $frontendUser->start($request);

        return $request->withAttribute('frontend.user', $frontendUser);
    }
}
