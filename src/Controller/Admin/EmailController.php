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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenDxp\Bundle\AdminBundle\Handler\Email\BlocklistPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Email\CreateBlocklistEntryHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\DeleteBlocklistEntryHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\DeleteEmailLogHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\GetBlocklistHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\GetEmailLogHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\GetEmailLogParamsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\GetEmailLogsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\ResendEmailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\SendTestEmailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\UpdateBlocklistEntryHandler;
use OpenDxp\Http\RequestHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

/**
 * @internal
 */
#[Route('/email')]
class EmailController extends AdminAbstractController
{
    #[IsGranted(new Expression('is_granted("emails") or is_granted("gdpr_data_extractor")'))]
    #[Route('/email-logs', name: 'opendxp_admin_email_emaillogs', methods: ['GET', 'POST'])]
    public function emailLogsAction(Request $request, GetEmailLogsHandler $getEmailLogs): JsonResponse
    {
        $result = $getEmailLogs(
            documentId: $request->request->has('documentId') ? (int)$request->request->get('documentId') : null,
            limit: (int)$request->request->get('limit', 50),
            offset: (int)$request->request->get('start', 0),
            filter: $request->request->has('filter') ? $request->request->get('filter') : null,
        );

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/show-email-log', name: 'opendxp_admin_email_showemaillog', methods: ['GET'])]
    public function showEmailLogAction(
        GetEmailLogHandler $getEmailLog,
        GetEmailLogParamsHandler $getEmailLogParams,
        ?Profiler $profiler,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse|Response {
        if ($profiler) {
            $profiler->disable();
        }

        if ($type === 'params') {
            return $this->adminJson($getEmailLogParams(id: $id));
        }

        $result = $getEmailLog($id);

        if ($type === 'text') {
            return $this->render('@OpenDxpAdmin/admin/email/text.html.twig', ['log' => $result->textLog]);
        }

        if ($type === 'html') {
            return new Response($result->htmlLog, 200, [
                'Content-Security-Policy' => "default-src 'self'; style-src 'self' 'unsafe-inline'; img-src * data:",
            ]);
        }

        if ($type === 'details') {
            return $this->adminJson($result->objectVars);
        }

        return new Response('No Type specified');
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/delete-email-log', name: 'opendxp_admin_email_deleteemaillog', methods: ['DELETE'])]
    public function deleteEmailLogAction(Request $request, DeleteEmailLogHandler $deleteEmailLog): JsonResponse
    {
        $deleteEmailLog(id: (int)$request->request->get('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/resend-email', name: 'opendxp_admin_email_resendemail', methods: ['POST'])]
    public function resendEmailAction(Request $request, ResendEmailHandler $resendEmail): JsonResponse
    {
        $resendEmail(
            id: (int)$request->request->get('id'),
            fieldOverrides: [
                'from' => $request->request->get('from') ?: null,
                'to' => $request->request->get('to') ?: null,
                'cc' => $request->request->get('cc') ?: null,
                'bcc' => $request->request->get('bcc') ?: null,
                'replyto' => $request->request->get('replyto') ?: null,
            ],
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/send-test-email', name: 'opendxp_admin_email_sendtestemail', methods: ['POST'])]
    public function sendTestEmailAction(Request $request, SendTestEmailHandler $sendTestEmail): JsonResponse
    {
        // Simulate a frontend request to prefix assets
        $request->attributes->set(RequestHelper::ATTRIBUTE_FRONTEND_REQUEST, true);

        $mailParamsArray = null;
        if ($request->request->has('mailParamaters')) {
            $mailParamsArray = json_decode($request->request->get('mailParamaters'), true) ?: null;
        }

        $sendTestEmail(
            emailType: (string)$request->request->get('emailType'),
            content: $request->request->get('content'),
            documentPath: $request->request->get('documentPath'),
            mailParameters: $mailParamsArray,
            from: $request->request->get('from'),
            to: (string)$request->request->get('to'),
            subject: (string)$request->request->get('subject'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/blocklist', name: 'opendxp_admin_email_blocklist', methods: ['POST'])]
    public function blocklistAction(
        BlocklistPayload $payload,
        GetBlocklistHandler $getBlocklist,
        CreateBlocklistEntryHandler $createBlocklistEntry,
        UpdateBlocklistEntryHandler $updateBlocklistEntry,
        DeleteBlocklistEntryHandler $deleteBlocklistEntry,
        #[MapQueryParameter] ?string $xaction = null,
    ): JsonResponse {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->destroyBlocklistEntry($deleteBlocklistEntry, $payload),
                'update'  => $this->adminJson(ApiResponse::ok(['data' => $updateBlocklistEntry($payload)])),
                'create'  => $this->adminJson(ApiResponse::ok(['data' => $createBlocklistEntry($payload)])),
                default   => throw new BadRequestHttpException(),
            };
        }

        $result = $getBlocklist($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    private function destroyBlocklistEntry(DeleteBlocklistEntryHandler $handler, BlocklistPayload $payload): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }
}
