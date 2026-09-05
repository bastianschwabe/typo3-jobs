<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\Widgets;

use BastianSchwabe\Jobs\Configuration\TrackingSettings;
use BastianSchwabe\Jobs\Service\Backend\ExtensionConfigurationLinkProvider;
use BastianSchwabe\Jobs\Service\Tracking\StatisticService;
use BastianSchwabe\Jobs\Widgets\JobStatisticWidget;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfiguration;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The widget only exists when EXT:dashboard is loaded, so this test loads it
 * explicitly. The registration test runs without it on purpose.
 */
final class JobStatisticWidgetTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['dashboard'];
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/jobs.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tracking.csv');

        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function widgetIsRegisteredInItsOwnGroup(): void
    {
        $registry = $this->get(WidgetRegistry::class);

        self::assertArrayHasKey('jobsStatistics', $registry->getAllWidgets());
        self::assertArrayHasKey('jobsStatistics', $registry->getAvailableWidgetsForWidgetGroup('jobs'));
    }

    #[Test]
    public function widgetRendersBothChartsAndLinksToTheModule(): void
    {
        $request = (new ServerRequest('https://example.org/typo3/ajax/dashboard/widget/get', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $configuration = new WidgetConfiguration(
            identifier: 'jobsStatistics',
            serviceName: 'dashboard.widget.jobs.statistics',
            groupNames: ['jobs'],
            title: 'jobs.backend:widget.title',
            description: 'jobs.backend:widget.description',
            iconIdentifier: 'jobs-module-statistics',
            height: 'medium',
            width: 'medium',
        );

        $widget = $this->createWidget($configuration);

        $result = $widget->renderWidget(new WidgetContext('jobsStatistics', [], $configuration, new Settings([]), $request));

        self::assertTrue($result->refreshable);
        self::assertStringContainsString('Top 5 by views', $result->content);
        self::assertStringContainsString('Top 5 by applications', $result->content);
        self::assertStringContainsString('Marketing Manager', $result->content);
        self::assertStringContainsString('/module/jobs/statistics', $result->content);
        self::assertSame(['EXT:jobs/Resources/Public/Css/Backend.css'], $widget->getCssFiles());

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['jobs']['tracking']['enabled'] = '0';
        $disabled = $widget->renderWidget(new WidgetContext('jobsStatistics', [], $configuration, new Settings([]), $request));

        self::assertStringContainsString('Statistics are disabled', $disabled->content);
        self::assertStringNotContainsString('Top 5 by views', $disabled->content);
    }

    private function createWidget(WidgetConfiguration $configuration): JobStatisticWidget
    {
        return new JobStatisticWidget(
            $configuration,
            $this->get(StatisticService::class),
            $this->get(BackendViewFactory::class),
            $this->get(UriBuilder::class),
            $this->get(TrackingSettings::class),
            $this->get(ExtensionConfigurationLinkProvider::class),
        );
    }
}
