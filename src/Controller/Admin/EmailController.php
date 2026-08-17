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
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Email\BlocklistPayload;
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
use OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogDetails\GetEmailLogDetailsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogHtml\GetEmailLogHtmlHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogParams\GetEmailLogParamsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogText\GetEmailLogTextHandler;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdBodyPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Security\AdminPermission;
use OpenDxp\Http\RequestHelper;
use OpenDxp\Security\CorePermission;
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
        GetEmailLogsHandler $handler,
        GetEmailLogsPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/show-email-log', name: 'opendxp_admin_email_showemaillog', methods: ['GET'])]
    public function showEmailLogAction(
        Request $request,
        ?Profiler $profiler,
        #[MapQueryParameter] ?string $type = null,
    ): Response {
        $profiler?->disable();

        return match ($type) {
            'params' => $this->forward(self::class . '::showEmailLogParamsAction', [], $request->query->all()),
            'text' => $this->forward(self::class . '::showEmailLogTextAction', [], $request->query->all()),
            'html' => $this->forward(self::class . '::showEmailLogHtmlAction', [], $request->query->all()),
            'details' => $this->forward(self::class . '::showEmailLogDetailsAction', [], $request->query->all()),
            default => throw new AdminOperationFailedException(sprintf('Invalid email log type "%s"', $type ?? '')),
        };
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/show-email-log/params', name: 'opendxp_admin_email_showemaillog_params', methods: ['GET'])]
    public function showEmailLogParamsAction(
        GetEmailLogParamsHandler $handler,
        IdQueryPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'params');
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/show-email-log/text', name: 'opendxp_admin_email_showemaillog_text', methods: ['GET'])]
    public function showEmailLogTextAction(
        GetEmailLogTextHandler $handler,
        IdQueryPayload $payload,
    ): Response {
        return $this->render('@OpenDxpAdmin/admin/email/text.html.twig', ['log' => $handler($payload)->textLog]);
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/show-email-log/html', name: 'opendxp_admin_email_showemaillog_html', methods: ['GET'])]
    public function showEmailLogHtmlAction(
        GetEmailLogHtmlHandler $handler,
        IdQueryPayload $payload,
    ): Response {
        return new Response($handler($payload)->htmlLog, 200, [
            'Content-Security-Policy' => "default-src 'self'; style-src 'self' 'unsafe-inline'; img-src * data:",
        ]);
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/show-email-log/details', name: 'opendxp_admin_email_showemaillog_details', methods: ['GET'])]
    public function showEmailLogDetailsAction(
        GetEmailLogDetailsHandler $handler,
        IdQueryPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'objectVars');
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/delete-email-log', name: 'opendxp_admin_email_deleteemaillog', methods: ['DELETE'])]
    public function deleteEmailLogAction(
        DeleteEmailLogHandler $handler,
        IdBodyPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/resend-email', name: 'opendxp_admin_email_resendemail', methods: ['POST'])]
    public function resendEmailAction(
        ResendEmailHandler $handler,
        ResendEmailPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/send-test-email', name: 'opendxp_admin_email_sendtestemail', methods: ['POST'])]
    public function sendTestEmailAction(
        Request $request,
        SendTestEmailHandler $handler,
        SendTestEmailPayload $payload,
    ): JsonResponse {
        // Simulate a frontend request to prefix assets
        $request->attributes->set(RequestHelper::ATTRIBUTE_FRONTEND_REQUEST, true);

        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/blocklist', name: 'opendxp_admin_email_blocklist', methods: ['POST'])]
    public function blocklistAction(
        Request $request,
        BlocklistPayload $payload,
        GetBlocklistHandler $handler,
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

        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/blocklist-destroy', name: 'opendxp_admin_email_blocklist_destroy', methods: ['POST'])]
    public function blocklistDestroyAction(
        BlocklistPayload $payload,
        DeleteBlocklistEntryHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/blocklist-update', name: 'opendxp_admin_email_blocklist_update', methods: ['POST'])]
    public function blocklistUpdateAction(
        BlocklistPayload $payload,
        UpdateBlocklistEntryHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Emails->value)]
    #[Route('/blocklist-create', name: 'opendxp_admin_email_blocklist_create', methods: ['POST'])]
    public function blocklistCreateAction(
        BlocklistPayload $payload,
        CreateBlocklistEntryHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }
}
