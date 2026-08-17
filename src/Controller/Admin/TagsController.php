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
use OpenDxp\Bundle\AdminBundle\Handler\Tags\AddTag\AddTagHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\AddTag\AddTagPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\AddTagToElement\AddTagToElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\AddTagToElement\AddTagToElementPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\DeleteTag\DeleteTagHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\DeleteTag\DeleteTagPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\DoBatchAssignment\DoBatchAssignmentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\DoBatchAssignment\DoBatchAssignmentPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\GetBatchAssignmentJobs\GetBatchAssignmentJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\GetBatchAssignmentJobs\GetBatchAssignmentJobsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\GetTagTreeChildren\GetTagTreeChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\GetTagTreeChildren\GetTagTreeChildrenPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\LoadTagsForElement\GetTagsForElement\GetTagsForElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\LoadTagsForElement\LoadTagsForElementPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\RemoveTagFromElement\RemoveTagFromElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\RemoveTagFromElement\RemoveTagFromElementPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\UpdateTag\UpdateTagHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Tags\UpdateTag\UpdateTagPayload;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/tags')]
class TagsController extends AdminAbstractController
{
    #[IsGranted(CorePermission::TagsConfiguration->value)]
    #[Route('/add', name: 'opendxp_admin_tags_add', methods: ['POST'])]
    public function addAction(
        AddTagPayload $payload,
        AddTagHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::TagsConfiguration->value)]
    #[Route('/delete', name: 'opendxp_admin_tags_delete', methods: ['DELETE'])]
    public function deleteAction(
        DeleteTagPayload $payload,
        DeleteTagHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::TagsConfiguration->value)]
    #[Route('/update', name: 'opendxp_admin_tags_update', methods: ['PUT'])]
    public function updateAction(
        UpdateTagPayload $payload,
        UpdateTagHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/tree-get-children-by-id', name: 'opendxp_admin_tags_treegetchildrenbyid', methods: ['GET'])]
    public function treeGetChildrenByIdAction(
        GetTagTreeChildrenPayload $payload,
        GetTagTreeChildrenHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'tags');
    }

    #[Route('/load-tags-for-element', name: 'opendxp_admin_tags_loadtagsforelement', methods: ['GET'])]
    public function loadTagsForElementAction(
        LoadTagsForElementPayload $payload,
        GetTagsForElementHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'tags');
    }

    #[Route('/add-tag-to-element', name: 'opendxp_admin_tags_addtagtoelement', methods: ['PUT'])]
    public function addTagToElementAction(
        AddTagToElementPayload $payload,
        AddTagToElementHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/remove-tag-from-element', name: 'opendxp_admin_tags_removetagfromelement', methods: ['DELETE'])]
    public function removeTagFromElementAction(
        RemoveTagFromElementPayload $payload,
        RemoveTagFromElementHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/get-batch-assignment-jobs', name: 'opendxp_admin_tags_getbatchassignmentjobs', methods: ['GET'])]
    public function getBatchAssignmentJobsAction(
        GetBatchAssignmentJobsPayload $payload,
        GetBatchAssignmentJobsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/do-batch-assignment', name: 'opendxp_admin_tags_dobatchassignment', methods: ['PUT'])]
    public function doBatchAssignmentAction(
        DoBatchAssignmentPayload $payload,
        DoBatchAssignmentHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }
}
