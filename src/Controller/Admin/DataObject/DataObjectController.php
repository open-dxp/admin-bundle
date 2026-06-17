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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\AddObjectFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\AddObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ChangeChildrenSortByHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\DeleteDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\DataObjectGridProxyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\TreeGetChildrenByIdHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetIdPathPagingInfoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetIdPathPagingInfo\GetIdPathPagingInfoPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectPreviewUrlHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectPreviewUrl\GetDataObjectPreviewUrlPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\SaveDataObjectFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\SaveDataObject\SaveDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\UpdateDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\SaveDataObject\SaveDataObjectPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\AddObjectFolderPayload;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\AddObjectPayload;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\ChangeChildrenSortByPayload;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\DataObjectGridProxyPayload;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\DeleteDataObjectPayload;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\GetDataObjectPayload;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\GetSelectOptionsPayload;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\SaveDataObjectFolderPayload;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\TreeGetChildrenByIdPayload;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\UpdateDataObjectPayload;
use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Exception\ElementLockedException;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenDxp\Controller\Traits\ElementEditLockHelperTrait;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element\ElementInterface;
use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

/**
 * @internal
 */
#[Route('/object', name: 'opendxp_admin_dataobject_dataobject_')]
#[IsGranted(CorePermission::Objects->value)]
class DataObjectController extends ElementControllerBase
{
    use ElementEditLockHelperTrait;

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
        TreeGetChildrenByIdPayload $payload,
        TreeGetChildrenByIdHandler $handler,
        #[MapQueryParameter] int $inSearch = 0,
    ): JsonResponse {
        $result = $handler($payload);

        if ($result->limit) {
            return $this->adminJson([
                'offset'     => $result->offset,
                'limit'      => $result->limit,
                'total'      => $result->total,
                'overflow'   => !is_null($result->filter) && ($result->filteredTotalCount > $result->limit),
                'nodes'      => $result->objects,
                'fromPaging' => $result->fromPaging,
                'filter'     => $result->filter ?: '',
                'inSearch'   => $inSearch,
            ]);
        }

        return $this->adminJson($result->objects);
    }

    #[Override]
    protected function getTreeNodeConfig(ElementInterface $element): array
    {
        return $this->elementService->getElementTreeNodeConfig($element);
    }

    #[Route('/get-id-path-paging-info', name: 'getidpathpaginginfo', methods: ['GET'])]
    public function getIdPathPagingInfoAction(GetIdPathPagingInfoHandler $handler, GetIdPathPagingInfoPayload $payload): JsonResponse
    {
        if ($payload->path === null) {
            return $this->adminJson(['success' => false]);
        }

        $result = $handler($payload);

        return $this->adminJson($result->data);
    }

    #[Route('/get', name: 'get', methods: ['GET'])]
    public function getAction(
        GetDataObjectPayload $payload,
        GetDataObjectHandler $getDataObject,
    ): JsonResponse {
        try {
            $result = $getDataObject($payload);
        } catch (ElementLockedException $e) {
            return $this->getEditLockResponse($e->getElementId(), $e->getElementType());
        }

        $this->sessionService->removeObject('object', $payload->id);

        return $this->adminJson($result->data);
    }

    #[Route('/get-select-options', name: 'getSelectOptions', methods: ['POST'])]
    public function getSelectOptions(
        GetSelectOptionsPayload $payload,
        GetSelectOptionsHandler $handler,
    ): JsonResponse {
        $options = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['options' => $options]));
    }

    #[Route('/get-folder', name: 'getfolder', methods: ['GET'])]
    public function getFolderAction(
        IdQueryPayload $payload,
        GetDataObjectFolderHandler $handler,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson($result->data);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function addAction(
        AddObjectPayload $payload,
        AddObjectHandler $handler,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok([
            'id'   => $result->id,
            'type' => $result->type,
        ]));
    }

    #[Route('/add-folder', name: 'addfolder', methods: ['POST'])]
    public function addFolderAction(
        AddObjectFolderPayload $payload,
        AddObjectFolderHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteAction(
        DeleteDataObjectPayload $payload,
        DeleteDataObjectHandler $handler,
    ): JsonResponse {
        if ($payload->type !== 'children' && !$payload->id) {
            throw new NotFoundHttpException();
        }

        $result = $handler($payload);

        if ($payload->type === 'children') {
            return $this->adminJson(ApiResponse::ok(['deleted' => $result->deleted]));
        }

        // return ok even when the object doesn't exist — valid for batch delete incl. children
        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/change-children-sort-by', name: 'changechildrensortby', methods: ['PUT'])]
    public function changeChildrenSortByAction(
        ChangeChildrenSortByPayload $payload,
        ChangeChildrenSortByHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/update', name: 'update', methods: ['PUT'])]
    public function updateAction(
        UpdateDataObjectPayload $payload,
        UpdateDataObjectHandler $handler,
    ): JsonResponse {
        try {
            $result = $handler($payload);
        } catch (\Throwable $e) {
            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }

        return $this->adminJson(ApiResponse::ok(['treeData' => $result->treeData]));
    }

    #[Route('/save', name: 'save', methods: ['POST', 'PUT'])]
    public function saveAction(SaveDataObjectHandler $handler, SaveDataObjectPayload $payload): JsonResponse
    {
        $result = $handler($payload);

        if ($payload->task === 'session' || $payload->task === 'scheduler') {
            return $this->adminJson(ApiResponse::ok());
        }

        if ($payload->task === 'publish' || $payload->task === 'unpublish') {
            return $this->adminJson(ApiResponse::ok([
                'general' => [
                    'modificationDate' => $result->modificationDate,
                    'versionDate'      => $result->versionDate,
                    'versionCount'     => $result->versionCount,
                ],
                'treeData' => $result->treeData,
            ]));
        }

        return $this->adminJson(ApiResponse::ok([
            'general' => [
                'modificationDate' => $result->modificationDate,
                'versionDate'      => $result->versionDate,
                'versionCount'     => $result->versionCount,
            ],
            'draft'    => $result->draftData,
            'treeData' => $result->treeData,
        ]));
    }

    #[Route('/save-folder', name: 'savefolder', methods: ['PUT'])]
    public function saveFolderAction(
        SaveDataObjectFolderPayload $payload,
        SaveDataObjectFolderHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
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

        return $this->adminJson($result->data);
    }

    #[Route('/preview', name: 'preview', methods: ['GET'])]
    public function previewAction(
        Request $request,
        GetDataObjectPreviewUrlHandler $handler,
        #[MapQueryParameter] int $id = 0,
    ): RedirectResponse|Response {
        $object = $this->sessionService->getObject('object', $id);

        if ($object instanceof DataObject\Concrete) {
            $payload = new GetDataObjectPreviewUrlPayload($object, ['context' => $this, ...$request->query->all()]);
            $redirectUrl = $handler($payload);

            return $this->redirect($redirectUrl);
        }

        throw new NotFoundHttpException(sprintf('Expected an object of type "%s", got "%s"', DataObject\Concrete::class, get_debug_type($object)));
    }
}
