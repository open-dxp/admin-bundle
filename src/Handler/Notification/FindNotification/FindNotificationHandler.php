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

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification\FindNotification;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\Notification\Service\NotificationService;
use UnexpectedValueException;

final class FindNotificationHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function __invoke(IdQueryPayload $payload): FindNotificationResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;

        try {
            $notification = $this->notificationService->findAndMarkAsRead($payload->id, $userId);
        } catch (UnexpectedValueException $e) {
            throw new AdminOperationFailedException(sprintf('Notification with id %d not found', $payload->id));
        }

        $data = $this->notificationService->format($notification);

        return new FindNotificationResult(data: $data);
    }
}
