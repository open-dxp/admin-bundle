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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification\SendNotification;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Notification\Service\NotificationService;
use OpenDxp\Model\User;

final class SendNotificationHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly NotificationService $notificationService,
    ) {}

    public function __invoke(SendNotificationPayload $payload): void
    {
        $fromUserId = (int) $this->userContext->getAdminUser()?->getId();

        $element = null;
        if ($payload->elementId && $payload->elementType) {
            $element = Service::getElementById($payload->elementType, $payload->elementId);
        }

        if (User::getById($payload->recipientId) instanceof User) {
            $this->notificationService->sendToUser($payload->recipientId, $fromUserId, $payload->title, $payload->message, $element);
        } else {
            $this->notificationService->sendToGroup($payload->recipientId, $fromUserId, $payload->title, $payload->message, $element);
        }
    }
}
