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

namespace OpenDxp\Bundle\AdminBundle\EventListener;

use OpenDxp;
use OpenDxp\Event\SystemEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @internal
 */
class AdminConfigListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::GET_SYSTEM_CONFIGURATION => 'updateSystemConfiguration',
        ];
    }

    public function updateSystemConfiguration(GenericEvent $event): void
    {
        $arguments = $event->getArguments();
        $config = $arguments['settings'];

        if (!$config || !$container = OpenDxp::getContainer()) {
            return;
        }

        $adminConfig = $container->getParameter('opendxp_admin.config');
        $configuration = array_merge_recursive($config, $adminConfig);

        $event->setArgument('settings', $configuration);
    }
}
