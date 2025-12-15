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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * Adds all services with the tags "opendxp_admin.serializer.encoder" and "opendxp_admin.serializer.normalizer" as
 * encoders and normalizers to the Admin Serializer service.
 *
 * This does exactly the same as the framework serializer pass, but adds encoders/normalizers to our custom admin
 * serializer.
 *
 * @see \Symfony\Component\Serializer\Serializer
 *
 * @internal
 */
final class SerializerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('OpenDxp\\Admin\\Serializer')) {
            return;
        }

        $definition = $container->getDefinition('OpenDxp\\Admin\\Serializer');

        // Looks for all the services tagged "serializer.normalizer" and adds them to the Serializer service
        $normalizers = $this->findAndSortTaggedServices('opendxp_admin.serializer.normalizer', $container);

        if ($normalizers === []) {
            throw new RuntimeException('You must tag at least one service as "opendxp_admin.serializer.normalizer" to use the Admin Serializer service');
        }

        // Looks for all the services tagged "serializer.encoders" and adds them to the Serializer service
        $encoders = $this->findAndSortTaggedServices('opendxp_admin.serializer.encoder', $container);
        if ($encoders === []) {
            throw new RuntimeException('You must tag at least one service as "opendxp_admin.serializer.encoder" to use the Admin Serializer service');
        }

        $definition->setArguments([
            '$normalizers' => $normalizers,
            '$encoders' => $encoders,
        ]);
    }
}
