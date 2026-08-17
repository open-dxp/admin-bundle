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

namespace OpenDxp\Bundle\AdminBundle\EventListener;

use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @deprecated since 1.4 and will be removed with 2.0.
 *
 * @internal
 */
readonly class CurrentControllerListener implements EventSubscriberInterface
{
    public function __construct(private CurrentControllerContextInterface $currentControllerContext)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $callable = $event->getController();
        $controller = is_array($callable) ? $callable[0] : $callable;

        $this->currentControllerContext->setController(is_object($controller) ? $controller : null);
    }
}
