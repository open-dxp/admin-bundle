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
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\DeleteAllNotificationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\DeleteNotificationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\FindAllNotificationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\FindLastUnreadNotificationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\FindNotificationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\GetRecipientsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\MarkAsReadNotificationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Notification\SendNotificationHandler;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Notification\Service\NotificationService;
use OpenDxp\Model\Notification\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

/**
 * @internal
 */
#[Route('/notification')]
class NotificationController extends AdminAbstractController
{
    #[IsGranted(CorePermission::NotificationsSend->value)]
    #[Route('/recipients', name: 'opendxp_admin_notification_recipients', methods: ['GET'])]
    public function recipientsAction(
        GetRecipientsHandler $getRecipients,
        UserService $service,
        TranslatorInterface $translator,
    ): JsonResponse {
        $result = $getRecipients($service, $translator);

        return $this->adminJson($result->data);
    }

    #[IsGranted(CorePermission::NotificationsSend->value)]
    #[Route('/send', name: 'opendxp_admin_notification_send', methods: ['POST'])]
    public function sendAction(
        SendNotificationHandler $sendNotification,
        Request $request,
        NotificationService $service,
    ): JsonResponse {
        $elementId = (int) $request->request->get('elementId', 0);
        $elementType = $request->request->get('elementType');
        $element = null;

        if ($elementId && $elementType) {
            $element = Service::getElementById($elementType, $elementId);
        }

        $sendNotification(
            service: $service,
            recipientId: (int) $request->request->get('recipientId', 0),
            title: (string) $request->request->get('title', ''),
            message: (string) $request->request->get('message', ''),
            element: $element,
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/find', name: 'opendxp_admin_notification_find', methods: ['GET'])]
    public function findAction(
        FindNotificationHandler $findNotification,
        NotificationService $service,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse {
        try {
            $result = $findNotification($service, $id);

            return $this->adminJson(ApiResponse::ok(['data' => $result->data]));
        } catch (\Throwable) {
            return $this->adminJson(['success' => false]);
        }
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/find-all', name: 'opendxp_admin_notification_findall', methods: ['POST'])]
    public function findAllAction(
        FindAllNotificationsHandler $findAllNotifications,
        Request $request,
        NotificationService $service,
    ): JsonResponse {
        $result = $findAllNotifications(
            service: $service,
            request: $request,
            offset: $request->request->getInt('start'),
            limit: $request->request->getInt('limit', 40),
        );

        return $this->adminJson(ApiResponse::ok(['total' => $result->total, 'data' => $result->data]));
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/find-last-unread', name: 'opendxp_admin_notification_findlastunread', methods: ['GET'])]
    public function findLastUnreadAction(
        FindLastUnreadNotificationsHandler $findLastUnread,
        NotificationService $service,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $lastUpdate = null,
    ): JsonResponse {
        $result = $findLastUnread(
            service: $service,
            lastUpdate: $lastUpdate ?? time(),
        );

        return $this->adminJson(ApiResponse::ok(['total' => $result->total, 'data' => $result->data, 'unread' => $result->unread]));
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/mark-as-read', name: 'opendxp_admin_notification_markasread', methods: ['PUT'])]
    public function markAsReadAction(
        MarkAsReadNotificationHandler $handler,
        NotificationService $service,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse {
        $handler($service, $id);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/delete', name: 'opendxp_admin_notification_delete', methods: ['DELETE'])]
    public function deleteAction(
        DeleteNotificationHandler $handler,
        NotificationService $service,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse {
        $handler($service, $id);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Notifications->value)]
    #[Route('/delete-all', name: 'opendxp_admin_notification_deleteall', methods: ['DELETE'])]
    public function deleteAllAction(DeleteAllNotificationsHandler $deleteAllNotifications, NotificationService $service): JsonResponse
    {
        $deleteAllNotifications($service);

        return $this->adminJson(ApiResponse::ok());
    }
}
