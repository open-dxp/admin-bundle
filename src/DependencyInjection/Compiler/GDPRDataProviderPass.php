<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\DependencyInjection\Compiler;

use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider\Manager;
use OpenDxp\DependencyInjection\CollectionServiceLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class GDPRDataProviderPass implements CompilerPassInterface
{
    /**
     * Registers each service with tag opendxp.gdpr.data-provider as dataprovider for gdpr data extractor
     */
    public function process(ContainerBuilder $container): void
    {
        $providers = $container->findTaggedServiceIds('opendxp.gdpr.data-provider');

        $mapping = [];
        foreach (array_keys($providers) as $id) {
            $mapping[$id] = new Reference($id);
        }

        $collectionLocator = new Definition(CollectionServiceLocator::class, [$mapping]);
        $collectionLocator->setPublic(false);
        $collectionLocator->addTag('container.service_locator');

        $manager = $container->getDefinition(Manager::class);
        $manager->setArgument('$services', $collectionLocator);
    }
}
