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

use Exception;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
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
use OpenDxp\Model\Element\ValidationException;
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
    ): JsonResponse
    {
        try {
            $result = $handler($payload);

            $wfConfig = [
                'message' => $result->message,
                'notes_enabled' => $result->notesEnabled,
                'notes_required' => $result->notesRequired,
                'additional_fields' => $result->additionalFields,
            ];
        } catch (Exception $e) {
            $wfConfig = ['message' => $e->getMessage()];
        }

        return $this->adminJson($wfConfig);
    }

    #[Route('/submit-workflow-transition', name: 'opendxp_admin_workflow_submitworkflowtransition', methods: ['POST'])]
    public function submitWorkflowTransitionAction(
        SubmitWorkflowTransitionPayload $payload,
        SubmitWorkflowTransitionHandler $handler,
    ): JsonResponse {
        try {
            $result = $handler($payload);

            if ($result->blocked) {
                return $this->adminJson(ApiResponse::error('transition failed', ['reasons' => $result->blockerReasons]));
            }

            return $this->adminJson(ApiResponse::ok(['callback' => 'reloadObject']));
        } catch (ValidationException $e) {
            $reason = '';
            if (count($e->getSubItems()) > 0) {
                $reason = '<ul>' . implode('', array_map(static fn ($item) => '<li>' . $item . '</li>', $e->getSubItems())) . '</ul>';
            }

            return $this->adminJson(ApiResponse::error($e->getMessage(), ['reasons' => [$reason]]));
        } catch (Exception $e) {
            return $this->adminJson(ApiResponse::error('error performing action on this element', ['reasons' => [$e->getMessage()]]));
        }
    }

    #[Route('/submit-global-action', name: 'opendxp_admin_workflow_submitglobal', methods: ['POST'])]
    public function submitGlobalAction(
        SubmitGlobalActionPayload $payload,
        SubmitGlobalActionHandler $handler,
    ): JsonResponse {
        try {
            $handler($payload);

            return $this->adminJson(ApiResponse::ok(['callback' => 'reloadObject']));
        } catch (ValidationException $e) {
            $reason = '';
            if (count($e->getSubItems()) > 0) {
                $reason = '<ul>' . implode('', array_map(static fn ($item) => '<li>' . $item . '</li>', $e->getSubItems())) . '</ul>';
            }

            return $this->adminJson(ApiResponse::error($e->getMessage(), ['reasons' => [$reason]]));
        } catch (Exception $e) {
            return $this->adminJson(ApiResponse::error('error performing action on this element', ['reasons' => [$e->getMessage()]]));
        }
    }

    #[Route('/get-workflow-details', name: 'opendxp_admin_workflow_getworkflowdetailsstore')]
    public function getWorkflowDetailsStore(
        GetWorkflowDetailsPayload $payload,
        GetWorkflowDetailsHandler $handler,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => count($result->data)]));
    }

    #[Route('/show-graph', name: 'opendxp_admin_workflow_show_graph', methods: ['GET'])]
    public function showGraph(
        ShowGraphPayload $payload,
        GetWorkflowSvgHandler $handler,
    ): Response {
        $svg = $handler($payload);

        $response = new Response($svg);
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }

    #[Route('/modal-custom-html', name: 'opendxp_admin_workflow_modal_custom_html', methods: ['POST'])]
    public function getModalCustomHtml(
        GetModalCustomHtmlPayload $payload,
        GetModalCustomHtmlHandler $handler,
    ): JsonResponse
    {
        try {
            $result = $handler($payload);

            return $this->adminJson(ApiResponse::ok(['customHtml' => $result->customHtml]));
        } catch (Exception $e) {
            return $this->adminJson(ApiResponse::error($e->getMessage()));
        }
    }
}
