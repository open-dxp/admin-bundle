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

namespace OpenDxp\Bundle\AdminBundle\EventListener\Traits;

use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @internal
 */
trait ControllerTypeTrait
{
    /**
     * Get controller of specified type
     */
    protected function getControllerType(ControllerEvent $event, string $type): mixed
    {
        $callable = $event->getController();

        if (!is_array($callable) || count($callable) === 0) {
            return null;
        }

        $controller = $callable[0];
        if ($controller instanceof $type) {
            return $controller;
        }

        return null;
    }

    /**
     * Test if event controller is of the given type
     */
    protected function isControllerType(ControllerEvent $event, string $type): bool
    {
        $controller = $this->getControllerType($event, $type);

        return $controller && $controller instanceof $type;
    }
}
