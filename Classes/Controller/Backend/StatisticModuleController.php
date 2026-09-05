<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Controller\Backend;

use BastianSchwabe\Jobs\Configuration\TrackingSettings;
use BastianSchwabe\Jobs\Service\Backend\ExtensionConfigurationLinkProvider;
use BastianSchwabe\Jobs\Service\Tracking\StatisticService;
use BastianSchwabe\Jobs\Service\Tracking\TrackingService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Backend module "Jobs > Statistics" with three views, switched through the
 * doc header menu: job views and applications, filter usage, and functions
 * such as the reset. "Functions" rather than "settings", so it is not
 * mistaken for TYPO3's own settings modules.
 */
#[AsController]
final class StatisticModuleController
{
    private const VIEWS = [
        'jobs' => 'jobs_statistics',
        'filter' => 'jobs_statistics.filter',
        'functions' => 'jobs_statistics.functions',
    ];

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ComponentFactory $componentFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly FlashMessageService $flashMessageService,
        private readonly StatisticService $statisticService,
        private readonly TrackingService $trackingService,
        private readonly TrackingSettings $trackingSettings,
        private readonly ExtensionConfigurationLinkProvider $configurationLink,
    ) {}

    public function jobsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createView($request, 'jobs');
        $view->assignMultiple([
            'summaries' => $this->statisticService->getJobSummaries(),
            'charts' => $this->statisticService->getTopCharts(),
            'highlight' => (int)($request->getQueryParams()['job'] ?? 0),
        ]);

        return $view->renderResponse('Backend/StatisticJobs');
    }

    public function filterAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createView($request, 'filter');
        $view->assign('filters', $this->statisticService->getFilterSummaries());

        return $view->renderResponse('Backend/StatisticFilter');
    }

    public function functionsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createView($request, 'functions');
        $view->assign('resetUrl', (string)$this->uriBuilder->buildUriFromRoute('jobs_statistics.reset'));

        return $view->renderResponse('Backend/StatisticFunctions');
    }

    /**
     * POST only. Empties all tracking tables and returns to the functions
     * view, so a reload cannot repeat the reset by accident.
     */
    public function resetAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->trackingService->resetAll();

        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue(new FlashMessage(
            $this->getLanguageService()->sL('jobs.backend:statistic.functions.reset.done'),
            '',
            ContextualFeedbackSeverity::OK,
            true,
        ));

        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('jobs_statistics.functions'), 303);
    }

    private function createView(ServerRequestInterface $request, string $activeView): ModuleTemplate
    {
        $languageService = $this->getLanguageService();

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle(
            $languageService->sL('jobs.modules.statistics:title'),
            $languageService->sL('jobs.backend:statistic.view.' . $activeView),
        );

        $menu = $this->componentFactory->createMenu()
            ->setIdentifier('jobsStatisticView')
            ->setLabel($languageService->sL('jobs.backend:statistic.view.label'));
        foreach (self::VIEWS as $identifier => $route) {
            $menu->addMenuItem(
                $this->componentFactory->createMenuItem()
                    ->setTitle($languageService->sL('jobs.backend:statistic.view.' . $identifier))
                    ->setHref((string)$this->uriBuilder->buildUriFromRoute($route))
                    ->setActive($identifier === $activeView),
            );
        }
        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        $view->getDocHeaderComponent()->setShortcutContext(
            self::VIEWS[$activeView],
            $languageService->sL('jobs.modules.statistics:title')
            . ': ' . $languageService->sL('jobs.backend:statistic.view.' . $activeView),
        );

        $view->assignMultiple([
            'activeView' => $activeView,
            'trackingEnabled' => $this->trackingSettings->isEnabled(),
            'settingsUrl' => $this->configurationLink->getUrl(),
        ]);

        return $view;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
