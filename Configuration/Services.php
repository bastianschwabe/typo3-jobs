<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;
use TYPO3\CMS\Form\Event\BeforeEmailFinisherInitializedEvent;
use TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent;

/**
 * Registrations that depend on optional extensions. Services.yaml carries
 * everything unconditional.
 *
 * The package manager is not available while the container is built, so the
 * optional extensions are detected through one of their classes.
 *
 * Without EXT:dashboard the widget class would not even load, and with it the
 * "dashboard.widget" tag is picked up by the dashboard's compiler pass
 * regardless of package order.
 *
 * The EXT:form listeners type-hint that extension's events, so without it
 * they must never be registered.
 */
return static function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder): void {
    if (interface_exists(WidgetRendererInterface::class)) {
        $configurator->services()
            ->set('dashboard.widget.jobs.statistics')
            ->class(Widgets\JobStatisticWidget::class)
            ->autowire()
            ->tag('dashboard.widget', [
                'identifier' => 'jobsStatistics',
                'groupNames' => 'jobs',
                'title' => 'jobs.backend:widget.title',
                'description' => 'jobs.backend:widget.description',
                'iconIdentifier' => 'jobs-module-statistics',
                'height' => 'medium',
                'width' => 'medium',
            ]);
    }

    if (class_exists(BeforeEmailFinisherInitializedEvent::class)) {
        $services = $configurator->services();
        $services
            ->set(EventListener\Form\PrefillApplicationForm::class)
            ->autowire()
            ->tag('event.listener', [
                'identifier' => 'jobs/form/prefill-application',
                'event' => BeforeRenderableIsRenderedEvent::class,
            ]);
        $services
            ->set(EventListener\Form\ResolveApplicationRecipient::class)
            ->autowire()
            ->tag('event.listener', [
                'identifier' => 'jobs/form/resolve-application-recipient',
                'event' => BeforeEmailFinisherInitializedEvent::class,
            ]);
    }
};
