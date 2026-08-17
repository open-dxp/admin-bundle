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

namespace OpenDxp\Bundle\AdminBundle\Attribute;

use Attribute;

/**
 * Documents that this controller action's Handler reads the current session id (via SessionIdentityInterface).
 * This is purely descriptive, so the endpoint's session footprint is visible at the very beginning of an action.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class SessionIdentityAware
{
}
