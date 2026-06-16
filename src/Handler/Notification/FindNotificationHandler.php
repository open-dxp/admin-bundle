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

use OpenDxp\Model\Notification\Service\NotificationService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnexpectedValueException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class FindNotificationHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(NotificationService $service, int $id): FindNotificationResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        try {
            $notification = $service->findAndMarkAsRead($id, $userId);
        } catch (UnexpectedValueException $e) {
            throw new NotFoundHttpException(sprintf('Notification with id %d not found', $id), $e);
        }

        $data = $service->format($notification);

        return new FindNotificationResult(data: $data);
    }
}
