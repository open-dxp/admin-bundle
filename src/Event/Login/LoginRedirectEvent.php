<?php

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

namespace OpenDxp\Bundle\AdminBundle\Event\Login;

use Symfony\Contracts\EventDispatcher\Event;

class LoginRedirectEvent extends Event
{
    public function __construct(protected string $routeName, protected array $routeParams = [])
    {
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function setRouteName(string $routeName): void
    {
        $this->routeName = $routeName;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function setRouteParams(array $routeParams): void
    {
        $this->routeParams = $routeParams;
    }
}
