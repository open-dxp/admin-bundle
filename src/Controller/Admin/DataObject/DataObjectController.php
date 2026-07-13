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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObject;

use OpenDxp\Bundle\AdminBundle\Controller\Admin\ElementControllerBase;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\AddObjectFolder\AddObjectFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\AddObjectFolder\AddObjectFolderPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\AddObject\AddObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\AddObject\AddObjectPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ChangeChildrenSortBy\ChangeChildrenSortByHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ChangeChildrenSortBy\ChangeChildrenSortByPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\DataObjectGridProxy\DataObjectGridProxyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\DataObjectGridProxy\DataObjectGridProxyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\DeleteDataObject\DeleteDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\DeleteDataObject\DeleteDataObjectPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObject\GetDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObject\GetDataObjectPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetSelectOptions\GetSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetSelectOptions\GetSelectOptionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\SaveDataObjectFolder\SaveDataObjectFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\SaveDataObjectFolder\SaveDataObjectFolderPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\TreeGetDataObjectChildren\TreeGetDataObjectChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\TreeGetDataObjectChildren\TreeGetDataObjectChildrenPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\UpdateDataObject\UpdateDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\UpdateDataObject\UpdateDataObjectPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectFolder\GetDataObjectFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetIdPathPagingInfo\GetIdPathPagingInfoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetIdPathPagingInfo\GetIdPathPagingInfoPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectPreviewUrl\GetDataObjectPreviewUrlHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectPreviewUrl\GetDataObjectPreviewUrlPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\SaveDataObject\SaveDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\SaveDataObject\SaveDataObjectPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * @internal
 */
#[Route('/object', name: 'opendxp_admin_dataobject_dataobject_')]
#[IsGranted(CorePermission::Objects->value)]
class DataObjectController extends ElementControllerBase
{
    /** On active edit lock answer with editlock response */
    public const string TASK_RESPONSE = 'response';

    /** On active edit lock overwrite with new user */
    public const string TASK_OVERWRITE = 'overwrite';

    /** On active edit lock keep existing entry */
    public const string TASK_KEEP = 'keep';

    public function __construct(
        ElementServiceInterface $elementService,
        private readonly SessionService $sessionService,
    ) {
        parent::__construct($elementService);
    }

    #[Route('/tree-get-children-by-id', name: 'treegetchildrenbyid', methods: ['GET'])]
    public function treeGetChildrenByIdAction(
        Request $request,
        TreeGetDataObjectChildrenPayload $payload,
    ): Response {
        return match ($payload->hasPagination()) {
            true  => $this->forward(self::class . '::treeGetChildrenByIdPaginatedAction', [], $request->query->all()),
            false => $this->forward(self::class . '::treeGetChildrenByIdListAction', [], $request->query->all()),
        };
    }

    #[Route('/tree-get-children-by-id-paginated', name: 'treegetchildrenbyidpaginated', methods: ['GET'])]
    public function treeGetChildrenByIdPaginatedAction(
        TreeGetDataObjectChildrenPayload $payload,
        TreeGetDataObjectChildrenHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/tree-get-children-by-id-list', name: 'treegetchildrenbyidlist', methods: ['GET'])]
    public function treeGetChildrenByIdListAction(
        TreeGetDataObjectChildrenPayload $payload,
        TreeGetDataObjectChildrenHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'nodes');
    }

    #[Route('/get-id-path-paging-info', name: 'getidpathpaginginfo', methods: ['GET'])]
    public function getIdPathPagingInfoAction(GetIdPathPagingInfoHandler $handler, GetIdPathPagingInfoPayload $payload): JsonResponse
    {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[Route('/get', name: 'get', methods: ['GET'])]
    public function getAction(
        GetDataObjectPayload $payload,
        GetDataObjectHandler $handler,
    ): JsonResponse {
        $result = $handler($payload);

        $this->sessionService->removeObject('object', $payload->id);

        return $this->apiJson($result, rootProperty: 'data');
    }

    #[Route('/get-select-options', name: 'getSelectOptions', methods: ['POST'])]
    public function getSelectOptions(
        GetSelectOptionsPayload $payload,
        GetSelectOptionsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/get-folder', name: 'getfolder', methods: ['GET'])]
    public function getFolderAction(
        IdQueryPayload $payload,
        GetDataObjectFolderHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function addAction(
        AddObjectPayload $payload,
        AddObjectHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/add-folder', name: 'addfolder', methods: ['POST'])]
    public function addFolderAction(
        AddObjectFolderPayload $payload,
        AddObjectFolderHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteAction(
        DeleteDataObjectPayload $payload,
        DeleteDataObjectHandler $handler,
    ): JsonResponse {
        $result = $handler($payload);

        if ($payload->type === 'children') {
            return $this->apiJson($result);
        }

        // return ok even when the object doesn't exist — valid for batch delete incl. children
        return $this->apiOk();
    }

    #[Route('/change-children-sort-by', name: 'changechildrensortby', methods: ['PUT'])]
    public function changeChildrenSortByAction(
        ChangeChildrenSortByPayload $payload,
        ChangeChildrenSortByHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/update', name: 'update', methods: ['PUT'])]
    public function updateAction(
        UpdateDataObjectPayload $payload,
        UpdateDataObjectHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/save', name: 'save', methods: ['POST', 'PUT'])]
    public function saveAction(SaveDataObjectHandler $handler, SaveDataObjectPayload $payload): JsonResponse
    {
        $result = $handler($payload);

        if ($payload->task === 'session' || $payload->task === 'scheduler') {
            return $this->apiOk();
        }

        return $this->apiJson($result, context: [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]);
    }

    #[Route('/save-folder', name: 'savefolder', methods: ['PUT'])]
    public function saveFolderAction(
        SaveDataObjectFolderPayload $payload,
        SaveDataObjectFolderHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/grid-proxy', name: 'gridproxy', methods: ['GET', 'POST', 'PUT'])]
    public function gridProxyAction(
        DataObjectGridProxyPayload $payload,
        DataObjectGridProxyHandler $handler,
        Request $request,
        CsrfProtectionHandler $csrfProtection,
    ): JsonResponse {
        $csrfProtection->checkCsrfToken($request);

        $result = $handler($payload);
        if ($result->requestedLanguage && $result->requestedLanguage !== 'default') {
            $request->setLocale($result->requestedLanguage);
        }

        return $this->apiJson($result, rootProperty: 'data');
    }

    #[Route('/preview', name: 'preview', methods: ['GET'])]
    public function previewAction(
        GetDataObjectPreviewUrlPayload $payload,
        GetDataObjectPreviewUrlHandler $handler,
    ): RedirectResponse {
        return $this->redirect($handler($payload));
    }
}
