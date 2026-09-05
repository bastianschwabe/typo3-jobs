<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Controller\Backend;

use BastianSchwabe\Jobs\Configuration\TrackingSettings;
use BastianSchwabe\Jobs\Enum\JobListFilter;
use BastianSchwabe\Jobs\Service\Backend\ExtensionConfigurationLinkProvider;
use BastianSchwabe\Jobs\Service\Backend\JobOverviewProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Backend module "Jobs > Jobs": every job in the installation in one table.
 */
#[AsController]
final class JobModuleController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly JobOverviewProvider $overviewProvider,
        private readonly TrackingSettings $trackingSettings,
        private readonly ExtensionConfigurationLinkProvider $configurationLink,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleData = $request->getAttribute('moduleData');
        $filter = $moduleData instanceof ModuleData
            ? (JobListFilter::tryFrom((string)$moduleData->get('filter', 'all')) ?? JobListFilter::All)
            : JobListFilter::All;

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($this->getLanguageService()->sL('jobs.modules.list:title'));
        $view->getDocHeaderComponent()->setShortcutContext(
            'jobs_list',
            $this->getLanguageService()->sL('jobs.modules.list:title'),
            ['filter' => $filter->value],
        );

        $view->assignMultiple([
            'filter' => $filter->value,
            'filters' => JobListFilter::cases(),
            'jobs' => $this->overviewProvider->getJobs($filter),
            'returnUrl' => (string)$request->getAttribute('normalizedParams')?->getRequestUri(),
            'trackingEnabled' => $this->trackingSettings->isEnabled(),
            'settingsUrl' => $this->configurationLink->getUrl(),
        ]);

        return $view->renderResponse('Backend/JobList');
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
