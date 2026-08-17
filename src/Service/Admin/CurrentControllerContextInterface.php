<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Service\Admin;

/**
 * Gives access to the controller instance handling the current request.
 *
 * @deprecated since 1.4 and will be removed with 2.0.
 */
interface CurrentControllerContextInterface
{
    public function getController(): ?object;

    public function setController(?object $controller): void;
}
