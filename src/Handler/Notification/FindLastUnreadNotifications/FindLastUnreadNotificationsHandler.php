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

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification\FindLastUnreadNotifications;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\Notification\Service\NotificationService;

final class FindLastUnreadNotificationsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function __invoke(FindLastUnreadNotificationsPayload $payload): FindLastUnreadNotificationsResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $result = $this->notificationService->findLastUnread($userId, $payload->lastUpdate ?? time());
        $unread = $this->notificationService->countAllUnread($userId);

        $data = [];
        foreach ($result['data'] as $notification) {
            $data[] = $this->notificationService->format($notification);
        }

        return new FindLastUnreadNotificationsResult(
            data: $data,
            total: (int) $result['total'],
            unread: $unread,
        );
    }
}
