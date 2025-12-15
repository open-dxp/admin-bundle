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

namespace OpenDxp\Bundle\AdminBundle\Event\Traits;

/**
 * Trait ElementDeleteInfoEventTrait
 *
 * @package OpenDxp\Event\Traits
 */
trait ElementDeleteInfoEventTrait
{
    protected bool $deletionAllowed = true;

    protected string $reason;

    public function getDeletionAllowed(): bool
    {
        return $this->deletionAllowed;
    }

    public function setDeletionAllowed(bool $deletionAllowed): void
    {
        $this->deletionAllowed = $deletionAllowed;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }
}
