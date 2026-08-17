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
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetModalCustomHtml\GetModalCustomHtmlHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetModalCustomHtml\GetModalCustomHtmlPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetWorkflowDetails\GetWorkflowDetailsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetWorkflowDetails\GetWorkflowDetailsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetWorkflowForm\GetWorkflowFormHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetWorkflowForm\GetWorkflowFormPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\ShowGraph\GetWorkflowSvg\GetWorkflowSvgHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\ShowGraph\ShowGraphPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\SubmitGlobalAction\SubmitGlobalActionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\SubmitGlobalAction\SubmitGlobalActionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\SubmitWorkflowTransition\SubmitWorkflowTransitionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\SubmitWorkflowTransition\SubmitWorkflowTransitionPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/workflow')]
class WorkflowController extends AdminAbstractController
{
    #[Route('/get-workflow-form', name: 'opendxp_admin_workflow_getworkflowform', methods: ['POST'])]
    public function getWorkflowFormAction(
        GetWorkflowFormPayload $payload,
        GetWorkflowFormHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[Route('/submit-workflow-transition', name: 'opendxp_admin_workflow_submitworkflowtransition', methods: ['POST'])]
    public function submitWorkflowTransitionAction(
        SubmitWorkflowTransitionPayload $payload,
        SubmitWorkflowTransitionHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/submit-global-action', name: 'opendxp_admin_workflow_submitglobal', methods: ['POST'])]
    public function submitGlobalAction(
        SubmitGlobalActionPayload $payload,
        SubmitGlobalActionHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/get-workflow-details', name: 'opendxp_admin_workflow_getworkflowdetailsstore')]
    public function getWorkflowDetailsStore(
        GetWorkflowDetailsPayload $payload,
        GetWorkflowDetailsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/show-graph', name: 'opendxp_admin_workflow_show_graph', methods: ['GET'])]
    public function showGraph(
        ShowGraphPayload $payload,
        GetWorkflowSvgHandler $handler,
    ): Response {
        $result = $handler($payload);

        $response = new Response($result->svg);
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }

    #[Route('/modal-custom-html', name: 'opendxp_admin_workflow_modal_custom_html', methods: ['POST'])]
    public function getModalCustomHtml(
        GetModalCustomHtmlPayload $payload,
        GetModalCustomHtmlHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }
}
