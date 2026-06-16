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
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetModalCustomHtmlHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetWorkflowDetailsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetWorkflowFormHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetWorkflowSvgHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\SubmitGlobalActionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\SubmitWorkflowTransitionHandler;
use OpenDxp\Model\Element\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/workflow')]
class WorkflowController extends AdminAbstractController
{
    #[Route('/get-workflow-form', name: 'opendxp_admin_workflow_getworkflowform', methods: ['POST'])]
    public function getWorkflowFormAction(Request $request, GetWorkflowFormHandler $getWorkflowForm): JsonResponse
    {
        try {
            [$ctype, $cid] = $this->resolveCtypeAndCid($request);
            $result = $getWorkflowForm(
                ctype: $ctype,
                cid: $cid,
                workflowName: $request->request->getString('workflowName'),
                transitionName: $request->request->getString('transitionName'),
            );

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
        Request $request,
        SubmitWorkflowTransitionHandler $submitWorkflowTransition,
    ): JsonResponse {
        try {
            [$ctype, $cid] = $this->resolveCtypeAndCid($request);
            $result = $submitWorkflowTransition(
                ctype: $ctype,
                cid: $cid,
                workflowName: $request->request->getString('workflowName'),
                transition: $request->request->getString('transition'),
                workflowOptions: $request->request->all('workflow'),
            );

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
        Request $request,
        SubmitGlobalActionHandler $submitGlobalAction,
    ): JsonResponse {
        try {
            [$ctype, $cid] = $this->resolveCtypeAndCid($request);
            $submitGlobalAction(
                ctype: $ctype,
                cid: $cid,
                workflowName: $request->request->getString('workflowName'),
                transition: $request->request->getString('transition'),
                workflowOptions: $request->request->all('workflow'),
            );

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
        GetWorkflowDetailsHandler $getWorkflowDetails,
        #[MapQueryParameter] string $ctype,
        #[MapQueryParameter] int $cid,
    ): JsonResponse {
        $result = $getWorkflowDetails(ctype: $ctype, cid: $cid);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => count($result->data)]));
    }

    #[Route('/show-graph', name: 'opendxp_admin_workflow_show_graph', methods: ['GET'])]
    public function showGraph(
        GetWorkflowSvgHandler $getWorkflowSvg,
        #[MapQueryParameter] string $ctype,
        #[MapQueryParameter] int $cid,
        #[MapQueryParameter] ?string $workflowName = null,
    ): Response {
        $svg = $getWorkflowSvg(ctype: $ctype, cid: $cid, workflowName: $workflowName);

        $response = new Response($svg);
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }

    #[Route('/modal-custom-html', name: 'opendxp_admin_workflow_modal_custom_html', methods: ['POST'])]
    public function getModalCustomHtml(Request $request, GetModalCustomHtmlHandler $getModalCustomHtml): JsonResponse
    {
        try {
            [$ctype, $cid] = $this->resolveCtypeAndCid($request);
            $result = $getModalCustomHtml(
                ctype: $ctype,
                cid: $cid,
                workflowName: $request->request->getString('workflowName'),
                transition: $request->request->getString('transition'),
                isGlobalAction: $request->request->getString('isGlobalAction') === 'true',
            );

            return $this->adminJson(ApiResponse::ok(['customHtml' => $result->customHtml]));
        } catch (Exception $e) {
            return $this->adminJson(ApiResponse::error($e->getMessage()));
        }
    }

    /** @return array{string, int} */
    private function resolveCtypeAndCid(Request $request): array
    {
        $ctype = $request->request->get('ctype') ?? $request->query->get('ctype');
        $cid = (int) ($request->request->get('cid') ?? $request->query->get('cid'));

        return [$ctype, $cid];
    }
}
