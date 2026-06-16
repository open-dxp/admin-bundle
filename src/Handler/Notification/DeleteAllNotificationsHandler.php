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

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Notification\Service\NotificationService;

final class DeleteAllNotificationsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(NotificationService $service): void
    {
        $service->deleteAll((int) $this->userContext->getAdminUser()?->getId());
    }
}
