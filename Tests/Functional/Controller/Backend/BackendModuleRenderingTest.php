<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\Controller\Backend;

use BastianSchwabe\Jobs\Controller\Backend\JobModuleController;
use BastianSchwabe\Jobs\Controller\Backend\StatisticModuleController;
use BastianSchwabe\Jobs\Domain\Repository\JobViewStatisticRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Renders both backend modules through their controllers. Rendering the real
 * Fluid templates is what catches wrong ViewHelper arguments, missing labels
 * and broken module links; the assertions on the content are deliberately
 * coarse.
 */
final class BackendModuleRenderingTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/jobs.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tracking.csv');

        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function jobModuleListsJobsWithStatusAndViewCount(): void
    {
        $request = $this->createModuleRequest('jobs_list', ['filter' => 'all']);
        $html = (string)$this->get(JobModuleController::class)->handleRequest($request)->getBody();

        self::assertStringContainsString('Backend Developer', $html);
        self::assertStringContainsString('Hidden Job', $html);
        self::assertStringContainsString('Berlin office', $html);
        self::assertStringContainsString('badge-success', $html, 'active badge');
        self::assertStringContainsString('badge-danger', $html, 'hidden badge');
        self::assertStringContainsString('Expired</span>', $html, 'expired badge for a passed validThrough');
        self::assertStringContainsString('/module/jobs/statistics', $html, 'view count links to the statistics');
        self::assertStringContainsString('#job-1', $html);
        self::assertStringContainsString('record/edit', $html, 'title links to the edit form');
    }

    #[Test]
    public function jobModuleFilterHidesInactiveJobs(): void
    {
        $request = $this->createModuleRequest('jobs_list', ['filter' => 'active']);
        $html = (string)$this->get(JobModuleController::class)->handleRequest($request)->getBody();

        self::assertStringContainsString('Backend Developer', $html);
        self::assertStringNotContainsString('Hidden Job', $html);
        self::assertStringContainsString('aria-current="page"', $html, 'active filter button is marked');
    }

    #[Test]
    public function statisticModuleJobsViewRendersChartsAndTable(): void
    {
        $request = $this->createModuleRequest('jobs_statistics', [], ['job' => '2']);
        $html = (string)$this->get(StatisticModuleController::class)->jobsAction($request)->getBody();

        self::assertStringContainsString('Top 5 by views', $html);
        self::assertStringContainsString('Top 5 by applications', $html);
        self::assertStringContainsString('style="width: 100%"', $html, 'largest bar fills the track');
        self::assertStringContainsString('Deleted job #999', $html, 'orphaned statistics stay visible');
        self::assertStringContainsString('jobs-table__row--highlight', $html, 'the linked job is highlighted');
        self::assertStringContainsString('50.0&nbsp;%', $html, 'conversion of job 1');
        self::assertStringContainsString('Backend.css', $html);
    }

    #[Test]
    public function statisticModuleFilterViewGroupsCombinations(): void
    {
        $request = $this->createModuleRequest('jobs_statistics.filter');
        $html = (string)$this->get(StatisticModuleController::class)->filterAction($request)->getBody();

        self::assertStringContainsString('Senior', $html);
        self::assertStringContainsString('Development', $html);
        self::assertStringContainsString('Berlin office', $html);
        self::assertStringContainsString('typo3', $html);
        self::assertStringContainsString('Part-time', $html, 'employment type is translated');
        self::assertStringContainsString('Remote only', $html);
    }

    #[Test]
    public function statisticModuleFunctionsViewOffersConfirmedReset(): void
    {
        $request = $this->createModuleRequest('jobs_statistics.functions');
        $html = (string)$this->get(StatisticModuleController::class)->functionsAction($request)->getBody();

        self::assertStringContainsString('t3js-modal-trigger', $html);
        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('/module/jobs/statistics/reset', $html);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function disabledViewProvider(): array
    {
        return [
            'job list' => ['jobs_list'],
            'statistics' => ['jobs_statistics'],
            'filters' => ['jobs_statistics.filter'],
            'functions' => ['jobs_statistics.functions'],
        ];
    }

    /**
     * One view per test: the module template keeps docheader state per
     * process, so rendering several views in one test is not representative.
     */
    #[Test]
    #[DataProvider('disabledViewProvider')]
    public function disabledTrackingGreysOutTheViewAndExplainsWhy(string $route): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['jobs']['tracking']['enabled'] = '0';
        $request = $this->createModuleRequest($route, ['filter' => 'all']);

        $html = (string)match ($route) {
            'jobs_list' => $this->get(JobModuleController::class)->handleRequest($request)->getBody(),
            'jobs_statistics' => $this->get(StatisticModuleController::class)->jobsAction($request)->getBody(),
            'jobs_statistics.filter' => $this->get(StatisticModuleController::class)->filterAction($request)->getBody(),
            'jobs_statistics.functions' => $this->get(StatisticModuleController::class)->functionsAction($request)->getBody(),
            default => self::fail('Unknown route ' . $route),
        };

        self::assertStringContainsString('Statistics are disabled', $html);
        self::assertStringContainsString('jobs-disabled', $html);

        if ($route === 'jobs_list') {
            self::assertStringContainsString('Backend Developer', $html, 'the job list itself stays usable');
        }
        if ($route === 'jobs_statistics.functions') {
            self::assertMatchesRegularExpression('/t3js-modal-trigger"\s+disabled/', $html, 'reset button is disabled');
        }
    }

    #[Test]
    public function resetEmptiesTheTablesAndRedirectsToFunctions(): void
    {
        $request = $this->createModuleRequest('jobs_statistics.reset')->withMethod('POST');
        $response = $this->get(StatisticModuleController::class)->resetAction($request);

        self::assertSame(303, $response->getStatusCode());
        self::assertStringContainsString('/module/jobs/statistics/functions', $response->getHeaderLine('Location'));
        self::assertSame(
            0,
            (int)$this->get(ConnectionPool::class)
                ->getConnectionForTable(JobViewStatisticRepository::TABLE)
                ->count('uid', JobViewStatisticRepository::TABLE, []),
        );
    }

    /**
     * @param array<string, string> $moduleData
     * @param array<string, string> $queryParams
     */
    private function createModuleRequest(string $routeIdentifier, array $moduleData = [], array $queryParams = []): ServerRequestInterface
    {
        $route = $this->get(Router::class)->getRoute($routeIdentifier);
        $moduleIdentifier = explode('.', $routeIdentifier)[0];
        $module = $this->get(ModuleProvider::class)->getModule($moduleIdentifier, $GLOBALS['BE_USER']);
        self::assertNotNull($module);

        $request = (new ServerRequest('https://example.org/typo3' . $route->getPath(), 'GET'))
            ->withQueryParams($queryParams)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $route)
            ->withAttribute('module', $module)
            ->withAttribute('moduleData', ModuleData::createFromModule($module, $moduleData));
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $GLOBALS['TYPO3_REQUEST'] = $request;

        return $request;
    }
}
