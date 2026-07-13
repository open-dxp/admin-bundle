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

namespace OpenDxp\Bundle\AdminBundle\Attribute;

use OpenDxp\Bundle\AdminBundle\Session\SessionGatewayInterface;

/**
 * Documents that this controller action's Handler reads/writes session state through the given gateway.
 * This is purely descriptive, so the endpoint's session footprint is visible at the very beginning of an action.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class SessionGatewayAware
{
    /**
     * @param class-string<SessionGatewayInterface> $gateway
     */
    public function __construct(public readonly string $gateway) {}
}