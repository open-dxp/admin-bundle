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

namespace OpenDxp\Bundle\AdminBundle\Exception;

use OpenDxp\Model\Element\Editlock;
use RuntimeException;

/**
 * Signals that an element is locked for editing by another session.
 * AdminExceptionListener maps it to a 200 JSON response carrying an "editlock" payload, which the admin UI uses to show the lock dialog.
 *
 * @see \OpenDxp\Bundle\AdminBundle\EventListener\AdminExceptionListener::onKernelException()
 */
final class ElementLockedException extends RuntimeException
{
    public function __construct(
        private readonly int $elementId,
        private readonly string $elementType,
        private readonly Editlock $editLock,
    ) {
        parent::__construct();
    }

    public function getElementId(): int
    {
        return $this->elementId;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getEditLock(): Editlock
    {
        return $this->editLock;
    }
}
