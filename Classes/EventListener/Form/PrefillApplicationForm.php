<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\EventListener\Form;

use BastianSchwabe\Jobs\Service\ApplicationFormResolver;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent;

/**
 * Hands the job the apply button linked from over to the form: its uid goes
 * into the hidden "jobUid" element and its title into "jobTitle".
 *
 * Runs when the form as a whole is rendered. Values already in the form state
 * are never overwritten, so a visitor who corrects the title, or a later step
 * of a multi-step form, keeps what is there.
 */
final class PrefillApplicationForm
{
    public function __construct(
        private readonly ApplicationFormResolver $resolver,
    ) {}

    public function __invoke(BeforeRenderableIsRenderedEvent $event): void
    {
        $form = $event->renderable;
        if (!$form instanceof FormDefinition || $form->getElementByIdentifier(ApplicationFormResolver::FIELD_JOB_UID) === null) {
            return;
        }

        $formRuntime = $event->formRuntime;
        if ($formRuntime[ApplicationFormResolver::FIELD_JOB_UID] !== null && $formRuntime[ApplicationFormResolver::FIELD_JOB_UID] !== '') {
            return;
        }

        $arguments = $formRuntime->getRequest()->getQueryParams()[ApplicationFormResolver::REQUEST_ARGUMENT] ?? null;
        $job = $this->resolver->resolveJob(is_array($arguments) ? ($arguments['job'] ?? null) : null);
        if ($job === null) {
            return;
        }

        $formRuntime[ApplicationFormResolver::FIELD_JOB_UID] = (int)$job->getUid();
        if ($form->getElementByIdentifier(ApplicationFormResolver::FIELD_JOB_TITLE) !== null) {
            $formRuntime[ApplicationFormResolver::FIELD_JOB_TITLE] = $this->resolver->describe($job);
        }
    }
}
