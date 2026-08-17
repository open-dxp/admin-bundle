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

namespace OpenDxp\Bundle\AdminBundle\Payload;

/**
 * Implemented by Payloads whose request must only act on the currently authenticated
 * user's own account. Consumed by OwnUserVoter via #[IsGranted(OwnUserVoter::OWN_USER)].
 */
interface OwnUserAwareInterface
{
    public function getOwnUserId(): int;
}
