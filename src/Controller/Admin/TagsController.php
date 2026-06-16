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
use OpenDxp\Bundle\AdminBundle\Handler\Tags\AddTagHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\AddTagToElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\DeleteTagHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\DoBatchAssignmentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\GetBatchAssignmentJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\GetTagsForElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\GetTagTreeChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\RemoveTagFromElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\UpdateTagHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

/**
 * @internal
 */
#[Route('/tags')]
class TagsController extends AdminAbstractController
{
    #[IsGranted(CorePermission::TagsConfiguration->value)]
    #[Route('/add', name: 'opendxp_admin_tags_add', methods: ['POST'])]
    public function addAction(Request $request, AddTagHandler $addTag): JsonResponse
    {
        $result = $addTag(
            name: strip_tags($request->request->get('text', '')),
            parentId: (int)$request->request->get('parentId'),
        );

        return $this->adminJson(ApiResponse::ok(['id' => $result->id]));
    }

    #[IsGranted(CorePermission::TagsConfiguration->value)]
    #[Route('/delete', name: 'opendxp_admin_tags_delete', methods: ['DELETE'])]
    public function deleteAction(Request $request, DeleteTagHandler $deleteTag): JsonResponse
    {
        $deleteTag(id: (int)$request->request->get('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::TagsConfiguration->value)]
    #[Route('/update', name: 'opendxp_admin_tags_update', methods: ['PUT'])]
    public function updateAction(Request $request, UpdateTagHandler $updateTag): JsonResponse
    {
        $parentId = $request->request->get('parentId');
        $updateTag(
            id: (int)$request->request->get('id'),
            parentId: ($parentId || $parentId === '0') ? (int)$parentId : null,
            name: $request->request->has('text') ? strip_tags($request->request->get('text', '')) : null,
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/tree-get-children-by-id', name: 'opendxp_admin_tags_treegetchildrenbyid', methods: ['GET'])]
    public function treeGetChildrenByIdAction(
        GetTagTreeChildrenHandler $getTagTreeChildren,
        #[MapQueryParameter] ?string $showSelection = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $assignmentCId = null,
        #[MapQueryParameter] string $assignmentCType = '',
        #[MapQueryParameter] ?string $node = null,
        #[MapQueryParameter] ?string $filter = null,
    ): JsonResponse {
        $result = $getTagTreeChildren(
            showSelection: $showSelection === 'true',
            assignmentCId: $assignmentCId,
            assignmentCType: strip_tags($assignmentCType),
            node: $node,
            filter: $filter,
        );

        return $this->adminJson($result->tags);
    }

    #[Route('/load-tags-for-element', name: 'opendxp_admin_tags_loadtagsforelement', methods: ['GET'])]
    public function loadTagsForElementAction(
        GetTagsForElementHandler $getTagsForElement,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $assignmentCId = null,
        #[MapQueryParameter] string $assignmentCType = '',
    ): JsonResponse {
        if (!$assignmentCId || !$assignmentCType) {
            return $this->adminJson([]);
        }

        $result = $getTagsForElement(
            assignmentId: $assignmentCId,
            assignmentType: strip_tags($assignmentCType),
        );

        return $this->adminJson($result->tags);
    }

    #[Route('/add-tag-to-element', name: 'opendxp_admin_tags_addtagtoelement', methods: ['PUT'])]
    public function addTagToElementAction(Request $request, AddTagToElementHandler $addTagToElement): JsonResponse
    {
        $result = $addTagToElement(
            tagId: (int)$request->request->get('tagId'),
            elementType: strip_tags($request->request->get('assignmentElementType', '')),
            elementId: (int)$request->request->get('assignmentElementId'),
        );

        return $this->adminJson(ApiResponse::ok(['id' => $result->id]));
    }

    #[Route('/remove-tag-from-element', name: 'opendxp_admin_tags_removetagfromelement', methods: ['DELETE'])]
    public function removeTagFromElementAction(Request $request, RemoveTagFromElementHandler $removeTagFromElement): JsonResponse
    {
        $result = $removeTagFromElement(
            tagId: (int)$request->request->get('tagId'),
            elementType: strip_tags($request->request->get('assignmentElementType', '')),
            elementId: (int)$request->request->get('assignmentElementId'),
        );

        return $this->adminJson(ApiResponse::ok(['id' => $result->id]));
    }

    #[Route('/get-batch-assignment-jobs', name: 'opendxp_admin_tags_getbatchassignmentjobs', methods: ['GET'])]
    public function getBatchAssignmentJobsAction(
        GetBatchAssignmentJobsHandler $getBatchAssignmentJobs,
        #[MapQueryParameter] int $elementId = 0,
        #[MapQueryParameter] string $elementType = '',
    ): JsonResponse {
        $result = $getBatchAssignmentJobs(
            elementType: strip_tags($elementType),
            elementId: $elementId,
        );

        return $this->adminJson(ApiResponse::ok(['idLists' => $result->idListParts, 'totalCount' => $result->totalCount]));
    }

    #[Route('/do-batch-assignment', name: 'opendxp_admin_tags_dobatchassignment', methods: ['PUT'])]
    public function doBatchAssignmentAction(Request $request, DoBatchAssignmentHandler $doBatchAssignment): JsonResponse
    {
        $doBatchAssignment(
            elementType: strip_tags($request->request->get('elementType', '')),
            elementIds: json_decode($request->request->get('childrenIds'), true) ?? [],
            assignedTags: json_decode($request->request->get('assignedTags'), true) ?? [],
            doCleanupTags: $request->request->get('removeAndApply') === 'true',
        );

        return $this->adminJson(ApiResponse::ok());
    }
}
