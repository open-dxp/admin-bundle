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

namespace OpenDxp\Bundle\AdminBundle\Event\Model;

use OpenDxp\Event\Model\ElementEventInterface;

interface ElementDeleteInfoEventInterface extends ElementEventInterface
{
    public function getDeletionAllowed(): bool;

    public function setDeletionAllowed(bool $deletionAllowed): void;

    public function getReason(): string;

    public function setReason(string $reason): void;
}
