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

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Notification\Service\NotificationService;
use OpenDxp\Model\User;

final class SendNotificationHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(
        NotificationService $service,
        int $recipientId,
        string $title,
        string $message,
        ?ElementInterface $element,
    ): void {
        $fromUserId = (int) $this->userContext->getAdminUser()?->getId();

        if (User::getById($recipientId) instanceof User) {
            $service->sendToUser($recipientId, $fromUserId, $title, $message, $element);
        } else {
            $service->sendToGroup($recipientId, $fromUserId, $title, $message, $element);
        }
    }
}
