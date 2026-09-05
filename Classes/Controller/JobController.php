<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Controller;

use BastianSchwabe\Jobs\Domain\Dto\JobDemand;
use BastianSchwabe\Jobs\Domain\Dto\SchemaContext;
use BastianSchwabe\Jobs\Domain\Model\Job;
use BastianSchwabe\Jobs\Domain\Repository\CompanyProfileRepository;
use BastianSchwabe\Jobs\Domain\Repository\JobRepository;
use BastianSchwabe\Jobs\Domain\Repository\LevelRepository;
use BastianSchwabe\Jobs\Domain\Repository\LocationRepository;
use BastianSchwabe\Jobs\Domain\Repository\OccupationalFieldRepository;
use BastianSchwabe\Jobs\Enum\ApplyType;
use BastianSchwabe\Jobs\Enum\EmploymentType;
use BastianSchwabe\Jobs\Service\JobPostingSchemaBuilder;
use BastianSchwabe\Jobs\Service\Tracking\SessionHashResolver;
use BastianSchwabe\Jobs\Service\Tracking\TrackingService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\ErrorController;

class JobController extends ActionController
{
    public function __construct(
        protected readonly JobRepository $jobRepository,
        protected readonly LevelRepository $levelRepository,
        protected readonly OccupationalFieldRepository $occupationalFieldRepository,
        protected readonly LocationRepository $locationRepository,
        protected readonly CompanyProfileRepository $companyProfileRepository,
        protected readonly JobPostingSchemaBuilder $schemaBuilder,
        protected readonly TrackingService $trackingService,
        protected readonly SessionHashResolver $sessionHashResolver,
    ) {}

    /**
     * Extbase refuses to map properties onto a non-entity argument unless they
     * are allow-listed. Naming them explicitly keeps the filter surface closed.
     */
    public function initializeListAction(): void
    {
        if (!$this->arguments->hasArgument('demand')) {
            return;
        }

        $this->arguments->getArgument('demand')
            ->getPropertyMappingConfiguration()
            ->allowProperties(
                'level',
                'occupationalField',
                'location',
                'employmentType',
                'remoteOnly',
                'search',
            );
    }

    public function listAction(?JobDemand $demand = null, int $currentPage = 1): ResponseInterface
    {
        // A demand only arrives when the visitor submitted the filter form;
        // the plugin's own pre-selection is not a filter the visitor chose.
        $submittedByVisitor = $demand !== null;
        $demand ??= $this->createDemandFromSettings();
        $jobs = $this->jobRepository->findByDemand($demand);

        // Only a submitted filter with at least one value set is recorded, and
        // only on the first page: paging through the same result would
        // otherwise inflate the combination.
        if ($submittedByVisitor && $demand->isActive() && $currentPage <= 1) {
            $this->trackFilter($demand, $jobs->count());
        }

        $paginator = new QueryResultPaginator(
            $jobs,
            max(1, $currentPage),
            max(1, (int)($this->settings['itemsPerPage'] ?? 10)),
        );

        $this->view->assignMultiple([
            'demand' => $demand,
            'jobs' => $paginator->getPaginatedItems(),
            // The paginator keeps getTotalAmountOfItems() protected, so the
            // total has to come from the query result itself.
            'totalJobs' => $jobs->count(),
            'paginator' => $paginator,
            'pagination' => new SimplePagination($paginator),
            'levels' => $this->levelRepository->findAll(),
            'occupationalFields' => $this->occupationalFieldRepository->findAll(),
            'locations' => $this->locationRepository->findAll(),
            'employmentTypes' => EmploymentType::cases(),
        ]);

        return $this->htmlResponse();
    }

    public function showAction(?Job $job = null): ResponseInterface
    {
        if ($job === null) {
            $this->throwNotFound();
        }

        $this->view->assignMultiple([
            'job' => $job,
            'schema' => $this->buildSchema($job),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Proxy in front of the application target: records the click, then sends
     * the visitor on to the job's application URL or e-mail address.
     *
     * Registered as non-cacheable, so every click reaches this action even
     * though the surrounding page is cached.
     */
    public function applyAction(?Job $job = null): ResponseInterface
    {
        if ($job === null) {
            $this->throwNotFound();
        }

        $target = $this->resolveApplicationTarget($job);
        if ($target === null) {
            // Nothing to apply to: back to the detail page instead of a dead end.
            return new RedirectResponse($this->buildJobUrl($job), 303);
        }

        [$type, $uri] = $target;

        $sessionHash = $this->sessionHashResolver->resolve($this->request);
        if ($sessionHash !== null) {
            $this->trackingService->trackApply($this->trackedUidOf($job), $sessionHash, $type);
        }

        // Not redirectToUri(): that prepends the base URI to anything that is
        // not http(s), which would break the mailto: target.
        return new RedirectResponse($uri, 302);
    }

    /**
     * @return array{0: ApplyType, 1: string}|null
     */
    protected function resolveApplicationTarget(Job $job): ?array
    {
        if ($job->getApplicationUrl() !== '') {
            $contentObject = $this->request->getAttribute('currentContentObject');
            $uri = $contentObject instanceof ContentObjectRenderer
                ? $contentObject->typoLink_URL([
                    'parameter' => $job->getApplicationUrl(),
                    'forceAbsoluteUrl' => true,
                ])
                : '';

            return $uri !== '' ? [ApplyType::Url, $uri] : null;
        }

        if ($job->getApplicationEmail() !== '') {
            return [ApplyType::Email, 'mailto:' . $job->getApplicationEmail()];
        }

        return null;
    }

    protected function trackFilter(JobDemand $demand, int $resultCount): void
    {
        $sessionHash = $this->sessionHashResolver->resolve($this->request);
        if ($sessionHash === null) {
            return;
        }

        $language = $this->request->getAttribute('language');
        $languageId = $language instanceof SiteLanguage ? $language->getLanguageId() : 0;

        $this->trackingService->trackFilter($demand, $resultCount, $sessionHash, $languageId);
    }

    /**
     * Extbase overlays translations onto the default-language record and keeps
     * that record's uid, so originals and translations share their statistics
     * without any extra lookup.
     */
    protected function trackedUidOf(Job $job): int
    {
        return (int)$job->getUid();
    }

    /**
     * The plugin was reached without a resolvable job, e.g. via a stale link.
     * A 404 is more useful than an empty page.
     */
    protected function throwNotFound(): never
    {
        throw new PropagateResponseException(
            GeneralUtility::makeInstance(ErrorController::class)
                ->pageNotFoundAction($this->request, 'Job not found.'),
            1755780000
        );
    }

    /**
     * Pre-filters configured in the plugin act as the initial demand when the
     * visitor has not submitted the filter form yet.
     */
    protected function createDemandFromSettings(): JobDemand
    {
        $demand = new JobDemand();
        $demand->setLevel((int)($this->settings['preselect']['level'] ?? 0));
        $demand->setOccupationalField((int)($this->settings['preselect']['occupationalField'] ?? 0));
        $demand->setLocation((int)($this->settings['preselect']['location'] ?? 0));

        return $demand;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSchema(Job $job): array
    {
        return $this->schemaBuilder->build($job, new SchemaContext(
            jobUrl: $this->buildJobUrl($job),
            organizationName: (string)($this->settings['organization']['name'] ?? ''),
            organizationUrl: (string)($this->settings['organization']['url'] ?? ''),
            organizationLogoUrl: $this->resolveOrganizationLogoUrl($job),
            imageUrls: $this->collectAbsoluteUrls($job->getImages()),
        ));
    }

    protected function buildJobUrl(Job $job): string
    {
        $uri = $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->uriFor('show', ['job' => $job->getUid()], 'Job');

        // absRefPrefix can still hand back a site-relative URI, but schema.org
        // consumers need an absolute one.
        return $this->makeAbsolute($uri, $this->request->getAttribute('normalizedParams')?->getSiteUrl() ?? '');
    }

    protected function resolveOrganizationLogoUrl(Job $job): string
    {
        $logo = $job->getPrimaryCompanyProfile()?->getLogo();
        if ($logo !== null) {
            $urls = $this->collectAbsoluteUrls($logo);

            return $urls[0] ?? '';
        }

        return (string)($this->settings['organization']['logo'] ?? '');
    }

    /**
     * @param ObjectStorage<FileReference>|FileReference $references
     * @return list<string>
     */
    protected function collectAbsoluteUrls(ObjectStorage|FileReference $references): array
    {
        $references = $references instanceof FileReference ? [$references] : $references;
        $siteUrl = $this->request->getAttribute('normalizedParams')?->getSiteUrl() ?? '';

        $urls = [];
        foreach ($references as $reference) {
            $publicUrl = $reference->getOriginalResource()->getPublicUrl();
            if ($publicUrl === null) {
                continue;
            }
            $urls[] = $this->makeAbsolute($publicUrl, $siteUrl);
        }

        return $urls;
    }

    protected function makeAbsolute(string $url, string $siteUrl): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim($siteUrl, '/') . '/' . ltrim($url, '/');
    }

}
