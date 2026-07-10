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
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Email\Blocklist\CreateBlocklistEntry\CreateBlocklistEntryHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\Blocklist\DeleteBlocklistEntry\DeleteBlocklistEntryHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\Blocklist\UpdateBlocklistEntry\UpdateBlocklistEntryHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\DeleteEmailLog\DeleteEmailLogHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\GetBlocklist\GetBlocklistHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\GetEmailLogs\GetEmailLogsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\GetEmailLogs\GetEmailLogsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Email\ResendEmail\ResendEmailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\ResendEmail\ResendEmailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Email\SendTestEmail\SendTestEmailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\SendTestEmail\SendTestEmailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogParams\GetEmailLogParamsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\ShowEmailLogHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\ShowEmailLogPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdBodyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Email\BlocklistPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\AdminPermission;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Http\RequestHelper;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/email')]
class EmailController extends AdminAbstractController
{
    #[IsGranted(new Expression(
        'is_granted("' . CorePermission::Emails->value . '") or is_granted("' . AdminPermission::GdprDataExtractor->value . '")'
    ))]
    #[Route('/email-logs', name: 'opendxp_admin_email_emaillogs', methods: ['GET', 'POST'])]
    public function emailLogsAction(
        GetEmailLogsHandler $getEmailLogs,
        GetEmailLogsPayload $payload,
    ): JsonResponse {
        $result = $getEmailLogs($payload);

        return $this->adminJson(ApiResponse::ok([
            'data' => $result->data,
            'total' => $result->total,
        ]));
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/show-email-log', name: 'opendxp_admin_email_showemaillog', methods: ['GET'])]
    public function showEmailLogAction(
        ShowEmailLogHandler $showEmailLog,
        GetEmailLogParamsHandler $getEmailLogParams,
        ShowEmailLogPayload $payload,
        ?Profiler $profiler,
    ): JsonResponse|Response {
        if ($profiler) {
            $profiler->disable();
        }

        return match ($payload->type) {
            'params' => $this->adminJson($getEmailLogParams($payload->id)),
            'text' => $this->render('@OpenDxpAdmin/admin/email/text.html.twig', ['log' => $showEmailLog($payload->id)->textLog]),
            'html' => new Response($showEmailLog($payload->id)->htmlLog, 200, [
                'Content-Security-Policy' => "default-src 'self'; style-src 'self' 'unsafe-inline'; img-src * data:",
            ]),
            'details' => $this->adminJson($showEmailLog($payload->id)->objectVars),
            default => new Response('No Type specified'),
        };
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/delete-email-log', name: 'opendxp_admin_email_deleteemaillog', methods: ['DELETE'])]
    public function deleteEmailLogAction(
        DeleteEmailLogHandler $deleteEmailLog,
        IdBodyPayload $payload,
    ): JsonResponse {
        $deleteEmailLog($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/resend-email', name: 'opendxp_admin_email_resendemail', methods: ['POST'])]
    public function resendEmailAction(
        ResendEmailHandler $resendEmail,
        ResendEmailPayload $payload,
    ): JsonResponse {
        $resendEmail($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/send-test-email', name: 'opendxp_admin_email_sendtestemail', methods: ['POST'])]
    public function sendTestEmailAction(
        Request $request,
        SendTestEmailHandler $sendTestEmail,
        SendTestEmailPayload $payload,
    ): JsonResponse {
        // Simulate a frontend request to prefix assets
        $request->attributes->set(RequestHelper::ATTRIBUTE_FRONTEND_REQUEST, true);

        $sendTestEmail($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/blocklist', name: 'opendxp_admin_email_blocklist', methods: ['POST'])]
    public function blocklistAction(
        Request $request,
        BlocklistPayload $payload,
        GetBlocklistHandler $getBlocklist,
        #[MapQueryParameter] ?string $xaction = null,
    ): Response {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->forward(self::class . '::blocklistDestroyAction', [], $request->query->all()),
                'update'  => $this->forward(self::class . '::blocklistUpdateAction', [], $request->query->all()),
                'create'  => $this->forward(self::class . '::blocklistCreateAction', [], $request->query->all()),
                default   => throw new AdminOperationFailedException(''),
            };
        }

        $result = $getBlocklist($payload);

        return $this->adminJson(ApiResponse::ok([
            'data' => $result->data,
            'total' => $result->total,
        ]));
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/blocklist-destroy', name: 'opendxp_admin_email_blocklist_destroy', methods: ['POST'])]
    public function blocklistDestroyAction(
        BlocklistPayload $payload,
        DeleteBlocklistEntryHandler $deleteBlocklistEntry,
    ): JsonResponse {
        $deleteBlocklistEntry($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/blocklist-update', name: 'opendxp_admin_email_blocklist_update', methods: ['POST'])]
    public function blocklistUpdateAction(
        BlocklistPayload $payload,
        UpdateBlocklistEntryHandler $updateBlocklistEntry,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $updateBlocklistEntry($payload)]));
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/blocklist-create', name: 'opendxp_admin_email_blocklist_create', methods: ['POST'])]
    public function blocklistCreateAction(
        BlocklistPayload $payload,
        CreateBlocklistEntryHandler $createBlocklistEntry,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $createBlocklistEntry($payload)]));
    }
}
