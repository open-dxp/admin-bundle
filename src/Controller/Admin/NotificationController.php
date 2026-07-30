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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\DeleteAllNotifications\DeleteAllNotificationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\DeleteNotification\DeleteNotificationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\FindAllNotifications\FindAllNotificationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\FindAllNotifications\FindAllNotificationsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\FindLastUnreadNotifications\FindLastUnreadNotificationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\FindLastUnreadNotifications\FindLastUnreadNotificationsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\FindNotification\FindNotificationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\GetRecipients\GetRecipientsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\MarkAsReadNotification\MarkAsReadNotificationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\SendNotification\SendNotificationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\SendNotification\SendNotificationPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/notification')]
class NotificationController extends AdminAbstractController
{
    #[IsGranted(CorePermission::NotificationsSend->value)]
    #[Route('/recipients', name: 'opendxp_admin_notification_recipients', methods: ['GET'])]
    public function recipientsAction(
        GetRecipientsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler(), rootProperty: 'data');
    }

    #[IsGranted(CorePermission::NotificationsSend->value)]
    #[Route('/send', name: 'opendxp_admin_notification_send', methods: ['POST'])]
    public function sendAction(
        SendNotificationHandler $handler,
        SendNotificationPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/find', name: 'opendxp_admin_notification_find', methods: ['GET'])]
    public function findAction(
        FindNotificationHandler $handler,
        IdQueryPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/find-all', name: 'opendxp_admin_notification_findall', methods: ['POST'])]
    public function findAllAction(
        FindAllNotificationsHandler $handler,
        FindAllNotificationsPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/find-last-unread', name: 'opendxp_admin_notification_findlastunread', methods: ['GET'])]
    public function findLastUnreadAction(
        FindLastUnreadNotificationsHandler $handler,
        FindLastUnreadNotificationsPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/mark-as-read', name: 'opendxp_admin_notification_markasread', methods: ['PUT'])]
    public function markAsReadAction(
        MarkAsReadNotificationHandler $handler,
        IdQueryPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/delete', name: 'opendxp_admin_notification_delete', methods: ['DELETE'])]
    public function deleteAction(
        DeleteNotificationHandler $handler,
        IdQueryPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/delete-all', name: 'opendxp_admin_notification_deleteall', methods: ['DELETE'])]
    public function deleteAllAction(
        DeleteAllNotificationsHandler $handler,
    ): JsonResponse {
        $handler();

        return $this->apiOk();
    }
}
