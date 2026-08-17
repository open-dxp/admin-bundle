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

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification\DeleteAllNotifications;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\Notification\Service\NotificationService;

final class DeleteAllNotificationsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function __invoke(): void
    {
        $this->notificationService->deleteAll((int) $this->userContext->getAdminUser()?->getId());
    }
}
