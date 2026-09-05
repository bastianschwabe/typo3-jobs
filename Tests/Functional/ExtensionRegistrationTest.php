<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional;

use BastianSchwabe\Jobs\Middleware\JobViewTracker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Http\MiddlewareStackResolver;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Frontend\Middleware\PageArgumentValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExtensionRegistrationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    /**
     * @return array<string, array{0: string}>
     */
    public static function pluginSignatureProvider(): array
    {
        return [
            'list plugin' => ['jobs_list'],
            'detail plugin' => ['jobs_show'],
        ];
    }

    #[Test]
    #[DataProvider('pluginSignatureProvider')]
    public function pluginIsRegisteredAsItsOwnContentType(string $signature): void
    {
        $items = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
        $values = array_column($items, 'value');

        self::assertContains($signature, $values, sprintf('CType "%s" is not registered.', $signature));
        self::assertArrayHasKey(
            $signature,
            $GLOBALS['TCA']['tt_content']['types'],
            sprintf('CType "%s" has no types entry.', $signature),
        );
    }

    #[Test]
    #[DataProvider('pluginSignatureProvider')]
    public function pluginCarriesItsFlexForm(string $signature): void
    {
        $dataStructure = $GLOBALS['TCA']['tt_content']['types'][$signature]['columnsOverrides']['pi_flexform']['config']['ds']
            ?? null;

        self::assertIsString($dataStructure);
        self::assertStringContainsString('EXT:jobs/Configuration/FlexForms/', $dataStructure);
    }

    /**
     * The filter form submits plain GET parameters, which cannot carry a cHash.
     * The list therefore renders as USER_INT and its parameters are kept out of
     * the cache hash. Drop either half and every filter URL turns into a 404.
     */
    #[Test]
    public function listPluginParametersAreExcludedFromTheCacheHash(): void
    {
        self::assertContains(
            '^tx_jobs_list[',
            $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] ?? [],
        );
    }

    #[Test]
    public function listActionIsRegisteredAsNonCacheable(): void
    {
        $nonCacheable = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['Jobs']['plugins']['List']['controllers']
            ?? [];

        $actions = [];
        foreach ($nonCacheable as $controller) {
            foreach ($controller['nonCacheableActions'] ?? [] as $action) {
                $actions[] = $action;
            }
        }

        self::assertContains('list', $actions);
    }

    /**
     * The apply proxy has to record every click, so it must not end up in the
     * page cache even though the surrounding detail plugin is cacheable.
     */
    #[Test]
    public function applyActionIsRegisteredAsNonCacheable(): void
    {
        $controllers = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['Jobs']['plugins']['Show']['controllers']
            ?? [];

        $actions = [];
        $nonCacheable = [];
        foreach ($controllers as $controller) {
            $actions = [...$actions, ...($controller['actions'] ?? [])];
            $nonCacheable = [...$nonCacheable, ...($controller['nonCacheableActions'] ?? [])];
        }

        self::assertContains('apply', $actions);
        self::assertContains('apply', $nonCacheable);
        self::assertNotContains('show', $nonCacheable, 'The detail view itself stays cacheable.');
    }

    #[Test]
    public function viewTrackerIsPartOfTheFrontendMiddlewareStack(): void
    {
        // The resolver hands the stack over in execution-reversed order: the
        // dispatcher runs the last entry first, so "after" means a lower index.
        $stack = array_values($this->get(MiddlewareStackResolver::class)->resolve('frontend')->getArrayCopy());

        self::assertContains(JobViewTracker::class, $stack);
        self::assertLessThan(
            array_search(PageArgumentValidator::class, $stack, true),
            array_search(JobViewTracker::class, $stack, true),
            'The tracker needs the decoded and validated route arguments.',
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function backendModuleProvider(): array
    {
        return [
            'module group' => ['jobs', ''],
            'job list' => ['jobs_list', 'jobs'],
            'statistics' => ['jobs_statistics', 'jobs'],
        ];
    }

    #[Test]
    #[DataProvider('backendModuleProvider')]
    public function backendModuleIsRegistered(string $identifier, string $parent): void
    {
        $moduleProvider = $this->get(ModuleProvider::class);

        self::assertTrue($moduleProvider->isModuleRegistered($identifier));
        self::assertSame($parent, $moduleProvider->getModule($identifier)?->getParentIdentifier());
    }

    #[Test]
    public function statisticsModuleExposesItsViewsAsRoutes(): void
    {
        $router = $this->get(Router::class);

        foreach (['jobs_statistics', 'jobs_statistics.filter', 'jobs_statistics.functions', 'jobs_statistics.reset'] as $route) {
            self::assertTrue($router->hasRoute($route), sprintf('Route "%s" is missing.', $route));
        }

        self::assertSame(['POST'], $router->getRoute('jobs_statistics.reset')->getMethods(), 'Reset must not be reachable via GET.');
    }

    #[Test]
    public function moduleLabelsResolveThroughTranslationDomains(): void
    {
        $languageService = $this->get(LanguageServiceFactory::class)->create('en');

        self::assertSame('Jobs', $languageService->sL('jobs.modules.jobs:title'));
        self::assertSame('Statistics', $languageService->sL('jobs.modules.statistics:title'));
        self::assertSame('Reset all statistics', $languageService->sL('jobs.backend:statistic.functions.reset.title'));
    }

    #[Test]
    public function listTypeIsNotUsedAnywhere(): void
    {
        // list_type was removed in v14; a leftover registration would be dead code.
        self::assertArrayNotHasKey('list_type', $GLOBALS['TCA']['tt_content']['columns'] ?? []);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function translationDomainProvider(): array
    {
        return [
            'table title' => ['jobs.db:tx_jobs_domain_model_job', 'Job'],
            'field label' => ['jobs.db:tx_jobs_domain_model_job.title', 'Job title'],
            'enum item' => ['jobs.db:employment_type.FULL_TIME', 'Full-time'],
            'frontend label' => ['jobs.messages:job.apply', 'Apply now'],
        ];
    }

    /**
     * v14 translation domains replace long LLL:EXT: paths. This asserts they
     * resolve for a third-party extension, not just for the Core.
     */
    #[Test]
    #[DataProvider('translationDomainProvider')]
    public function translationDomainResolves(string $reference, string $expected): void
    {
        $languageService = $this->get(LanguageServiceFactory::class)->create('en');

        self::assertSame($expected, $languageService->sL($reference));
    }

    #[Test]
    public function germanTranslationIsShipped(): void
    {
        $languageService = $this->get(LanguageServiceFactory::class)->create('de');

        self::assertSame('Bezeichnung', $languageService->sL('jobs.db:tx_jobs_domain_model_job.title'));
        self::assertSame('Jetzt bewerben', $languageService->sL('jobs.messages:job.apply'));
    }
}
