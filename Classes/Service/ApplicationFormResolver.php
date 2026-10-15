<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Service;

use BastianSchwabe\Jobs\Domain\Model\Job;
use BastianSchwabe\Jobs\Domain\Repository\JobRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Serves the glue between a job and an EXT:form application form.
 *
 * The apply button hands the job over as a plain uid. Everything else the form
 * needs (title, recipient) is looked up here, on the server, because anything
 * a hidden form field carries can be edited by the visitor.
 */
class ApplicationFormResolver
{
    /** Query argument the apply button appends to the URL of the form page. */
    public const REQUEST_ARGUMENT = 'tx_jobs_apply';

    /** Identifier of the form element that carries the job uid. */
    public const FIELD_JOB_UID = 'jobUid';

    /** Identifier of the (optional) form element that is pre-filled with the job title. */
    public const FIELD_JOB_TITLE = 'jobTitle';

    public function __construct(
        protected readonly JobRepository $jobRepository,
    ) {}

    /**
     * Only a job the frontend would show is accepted, so a stale or invented
     * uid falls back to the plain application form.
     */
    public function resolveJob(mixed $uid, ?\DateTimeImmutable $now = null): ?Job
    {
        if (!is_scalar($uid) || !ctype_digit((string)$uid) || (int)$uid <= 0) {
            return null;
        }

        $job = $this->jobRepository->findByUid((int)$uid);

        return $job instanceof Job && !$job->isExpired($now) ? $job : null;
    }

    /**
     * "Title (reference)", the reference only when the job has one.
     */
    public function describe(Job $job): string
    {
        return $job->getIdentifier() !== ''
            ? sprintf('%s (%s)', $job->getTitle(), $job->getIdentifier())
            : $job->getTitle();
    }

    /**
     * The application e-mail address wins; without one the first contact with
     * an address receives the application. Null leaves the choice to the form.
     *
     * @return array<string, string> address => name, as the EmailFinisher expects it
     */
    public function resolveRecipients(Job $job): array
    {
        if (GeneralUtility::validEmail($job->getApplicationEmail())) {
            return [$job->getApplicationEmail() => ''];
        }

        foreach ($job->getContacts() as $contact) {
            if (GeneralUtility::validEmail($contact->getEmail())) {
                return [$contact->getEmail() => $contact->getFullName()];
            }
        }

        return [];
    }
}
