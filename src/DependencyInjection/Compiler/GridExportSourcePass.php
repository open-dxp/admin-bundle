<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\DependencyInjection\Compiler;

use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @see AsGridExportSource
 */
final class GridExportSourcePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $sources = [];
        $permissions = [];
        $batchSizes = [];

        foreach ($container->findTaggedServiceIds(GridExportSourceRegistry::TAG) as $id => $tags) {
            foreach ($tags as $attributes) {
                $sources[$attributes['name']] = new Reference($id);
                $permissions[$attributes['name']] = $attributes['permission'];
                $batchSizes[$attributes['name']] = $attributes['batch_size'];
            }
        }

        $container
            ->getDefinition(GridExportSourceRegistry::class)
            ->setArgument('$sources', ServiceLocatorTagPass::register($container, $sources))
            ->setArgument('$permissions', $permissions)
            ->setArgument('$batchSizes', $batchSizes);
    }
}
