<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;

/**
 * Registrations that depend on optional extensions. Services.yaml carries
 * everything unconditional.
 *
 * The package manager is not available while the container is built, so the
 * dashboard is detected through its interface. Without EXT:dashboard the
 * widget class would not even load, and with it the "dashboard.widget" tag is
 * picked up by the dashboard's compiler pass regardless of package order.
 */
return static function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder): void {
    if (!interface_exists(WidgetRendererInterface::class)) {
        return;
    }

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
};
