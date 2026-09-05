<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Widgets;

use BastianSchwabe\Jobs\Configuration\TrackingSettings;
use BastianSchwabe\Jobs\Service\Backend\ExtensionConfigurationLinkProvider;
use BastianSchwabe\Jobs\Service\Tracking\StatisticService;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetResult;

/**
 * Dashboard widget with the two "top 5" charts of the statistics module.
 *
 * Only registered when EXT:dashboard is installed (see Configuration/Services.php),
 * which is why the class lives outside the autowired resource.
 */
final readonly class JobStatisticWidget implements WidgetRendererInterface, AdditionalCssInterface
{
    public function __construct(
        /** Injected by the dashboard's compiler pass from the service tag. */
        private WidgetConfigurationInterface $configuration,
        private StatisticService $statisticService,
        private BackendViewFactory $backendViewFactory,
        private UriBuilder $uriBuilder,
        private TrackingSettings $trackingSettings,
        private ExtensionConfigurationLinkProvider $configurationLink,
    ) {}

    public function getSettingsDefinitions(): array
    {
        return [];
    }

    public function renderWidget(WidgetContext $context): WidgetResult
    {
        // The widget is rendered through a dashboard route, so the dashboard
        // package (widget layout) and this one (charts) are added explicitly.
        $view = $this->backendViewFactory->create($context->request, ['typo3/cms-dashboard', 'bastianschwabe/jobs']);
        $enabled = $this->trackingSettings->isEnabled();
        $view->assignMultiple([
            'configuration' => $this->configuration,
            'trackingEnabled' => $enabled,
            'settingsUrl' => $enabled ? '' : $this->configurationLink->getUrl(),
            'charts' => $enabled ? $this->statisticService->getTopCharts() : [],
            'statisticsUrl' => (string)$this->uriBuilder->buildUriFromRoute('jobs_statistics'),
        ]);

        return new WidgetResult(
            content: $view->render('Widget/JobStatisticWidget'),
            refreshable: true,
        );
    }

    /**
     * @return list<string>
     */
    public function getCssFiles(): array
    {
        return ['EXT:jobs/Resources/Public/Css/Backend.css'];
    }
}
