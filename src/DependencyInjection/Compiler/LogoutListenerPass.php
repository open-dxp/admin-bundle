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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\DependencyInjection\Compiler;

use OpenDxp\Bundle\AdminBundle\Security\Event\LogoutListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The dispatcher of the admin firewall exists only after the security extension has loaded, so an extension cannot
 * check for it.
 *
 * @internal
 */
final class LogoutListenerPass implements CompilerPassInterface
{
    private const string DISPATCHER = 'security.event_dispatcher.opendxp_admin';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(LogoutListener::class)) {
            return;
        }

        if (!$container->hasDefinition(self::DISPATCHER)) {
            $container->removeDefinition(LogoutListener::class);

            return;
        }

        $container->getDefinition(LogoutListener::class)
            ->addTag('kernel.event_subscriber', ['dispatcher' => self::DISPATCHER]);
    }
}
