<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\EventListener\Form;

use BastianSchwabe\Jobs\Service\ApplicationFormResolver;
use TYPO3\CMS\Form\Event\BeforeEmailFinisherInitializedEvent;

/**
 * Sends the application to the address of the job the visitor applied for.
 *
 * Two options on an email finisher opt in, and each replaces one address list:
 *
 * - "jobsRecipient: true" replaces "recipients", for the mail that delivers
 *   the application.
 * - "jobsReplyTo: true" replaces "replyToRecipients", for the confirmation to
 *   the applicant, so that a reply reaches the same person.
 *
 * A finisher without them is left alone. The addresses written in the form
 * definition stay as the fallback for a form opened without a job, for an
 * unknown or expired job, and for a job without any address.
 */
final class ResolveApplicationRecipient
{
    /** Finisher option => the finisher option holding the address list it replaces. */
    private const REPLACEABLE = [
        'jobsRecipient' => 'recipients',
        'jobsReplyTo' => 'replyToRecipients',
    ];

    public function __construct(
        private readonly ApplicationFormResolver $resolver,
    ) {}

    public function __invoke(BeforeEmailFinisherInitializedEvent $event): void
    {
        $options = $event->getOptions();
        $targets = [];
        foreach (self::REPLACEABLE as $flag => $target) {
            if (filter_var($options[$flag] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $targets[] = $target;
            }
        }
        if ($targets === []) {
            return;
        }

        $formValues = $event->getFinisherContext()->getFormValues();
        $job = $this->resolver->resolveJob($formValues[ApplicationFormResolver::FIELD_JOB_UID] ?? null);
        if ($job === null) {
            return;
        }

        $addresses = $this->resolver->resolveRecipients($job);
        if ($addresses === []) {
            return;
        }

        foreach ($targets as $target) {
            $options[$target] = $addresses;
        }
        $event->setOptions($options);
    }
}
