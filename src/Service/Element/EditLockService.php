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

namespace OpenDxp\Bundle\AdminBundle\Service\Element;

use OpenDxp\Bundle\AdminBundle\Exception\ElementLockedException;
use OpenDxp\Model\Element\Editlock;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EditLockService
{
    private const string TASK_OVERWRITE = 'overwrite';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Checks whether the element is locked by another session and acquires the lock if not.
     *
     * Dispatches $eventName to allow subscribers to override the default TASK_RESPONSE behaviour
     * (e.g. TASK_OVERWRITE to force-acquire the lock and continue normally).
     *
     * @throws ElementLockedException when the element is locked and the event task remains TASK_RESPONSE
     */
    public function checkAndAcquire(int $id, string $type, string $eventName, mixed $element = null): void
    {
        $sessionId = $this->requestStack->getSession()->getId();

        if (!Editlock::isLocked($id, $type, $sessionId)) {
            Editlock::lock($id, $type, $sessionId);

            return;
        }

        $lockData = ['task' => 'response'];
        $eventArgs = ['data' => $lockData];
        if ($element !== null) {
            $eventArgs['object'] = $element;
        }

        $event = new GenericEvent(null, $eventArgs);
        $this->eventDispatcher->dispatch($event, $eventName);
        $task = $event->getArgument('data')['task'];

        if ($task === self::TASK_OVERWRITE) {
            Editlock::lock($id, $type, $sessionId);

            return;
        }

        $lock = Editlock::getByElement($id, $type);
        throw new ElementLockedException($id, $type, $lock);
    }
}
