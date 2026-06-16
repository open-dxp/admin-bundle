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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectPreviewUrlHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\SaveDataObjectFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\SaveDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\UpdateDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\DataObjectPayload;
use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Exception\ElementLockedException;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenDxp\Controller\Traits\ElementEditLockHelperTrait;
use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element;
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
        TreeGetChildrenByIdHandler $handler,
        Request $request,
        #[MapQueryParameter] ?string $filter = null,
        #[MapQueryParameter] int $node = 0,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] int $limit = 100000000,
        #[MapQueryParameter] string $view = '',
        #[MapQueryParameter] int $fromPaging = 0,
        #[MapQueryParameter] int $inSearch = 0,
    ): JsonResponse
    {
        $result = $handler($node, $filter, $start, $limit, $view, $fromPaging, $request->query->all());

        if ($result->limit) {
            return $this->adminJson([
                'offset' => $result->offset,
                'limit' => $result->limit,
                'total' => $result->total,
                'overflow' => !is_null($result->filter) && ($result->filteredTotalCount > $result->limit),
                'nodes' => $result->objects,
                'fromPaging' => $result->fromPaging,
                'filter' => $result->filter ?: '',
                'inSearch' => $inSearch,
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
    public function getIdPathPagingInfoAction(
        GetIdPathPagingInfoHandler $handler,
        #[MapQueryParameter] ?string $path = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $limit = null,
    ): JsonResponse
    {
        if ($path === null) {
            return $this->adminJson(['success' => false]);
        }

        $result = $handler($path, $limit ?? 30);

        return $this->adminJson($result->data);
    }

    #[Route('/get', name: 'get', methods: ['GET'])]
    public function getAction(
        GetDataObjectHandler $getDataObject,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $layoutId = null,
    ): JsonResponse
    {
        try {
            $result = $getDataObject($id, $layoutId);
        } catch (ElementLockedException $e) {
            return $this->getEditLockResponse($e->getElementId(), $e->getElementType());
        }

        $this->sessionService->removeObject('object', $id);

        return $this->adminJson($result->data);
    }

    #[Route('/get-select-options', name: 'getSelectOptions', methods: ['POST'])]
    public function getSelectOptions(Request $request, GetSelectOptionsHandler $handler): JsonResponse
    {
        $changedData = $request->request->has('changedData')
            ? $this->decodeJson($request->request->get('changedData'))
            : null;

        $options = $handler(
            objectId: $request->request->getInt('objectId'),
            changedData: is_array($changedData) ? $changedData : null,
            fieldDefinitionConfig: json_decode($request->request->get('fieldDefinition'), true),
            context: json_decode($request->request->get('context'), true) ?? [],
        );

        return $this->adminJson(ApiResponse::ok(['options' => $options]));
    }

    #[Route('/get-folder', name: 'getfolder', methods: ['GET'])]
    public function getFolderAction(
        GetDataObjectFolderHandler $handler,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse
    {
        $result = $handler($id);

        return $this->adminJson($result->data);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function addAction(Request $request, AddObjectHandler $handler): JsonResponse
    {
        $result = $handler(
            className: $request->request->get('className'),
            classId: $request->request->get('classId'),
            parentId: $request->request->getInt('parentId'),
            key: $request->request->get('key'),
            objectType: $request->request->get('objecttype') ?? '',
            variantViaTree: (bool) $request->request->get('variantViaTree'),
        );

        return $this->adminJson(ApiResponse::ok([
            'id' => $result->id,
            'type' => $result->type,
        ]));
    }

    #[Route('/add-folder', name: 'addfolder', methods: ['POST'])]
    public function addFolderAction(Request $request, AddObjectFolderHandler $handler): JsonResponse
    {
        $handler(
            parentId: $request->request->getInt('parentId'),
            key: $request->request->get('key'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteAction(DeleteDataObjectHandler $handler, Request $request): JsonResponse
    {
        $type = $request->request->get('type');
        $id = (int) $request->request->get('id');

        if ($type !== 'children' && !$id) {
            throw new NotFoundHttpException();
        }

        $result = $handler(
            type: $type ?? '',
            id: $id,
            amount: $request->request->getInt('amount'),
        );

        if ($type === 'children') {
            return $this->adminJson(ApiResponse::ok(['deleted' => $result->deleted]));
        }

        // return ok even when the object doesn't exist — valid for batch delete incl. children
        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/change-children-sort-by', name: 'changechildrensortby', methods: ['PUT'])]
    public function changeChildrenSortByAction(ChangeChildrenSortByHandler $handler, Request $request): JsonResponse
    {
        $handler(
            id: $request->request->getInt('id'),
            sortBy: $request->request->get('sortBy') ?? '',
            sortOrder: $request->request->get('childrenSortOrder') ?? '',
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/update', name: 'update', methods: ['PUT'])]
    public function updateAction(Request $request, UpdateDataObjectHandler $handler): JsonResponse
    {
        $values = $this->decodeJson($request->request->get('values'));
        $ids = $this->decodeJson($request->request->get('id'));

        if (is_array($ids)) {
            $result = null;
            foreach ($ids as $id) {
                try {
                    $result = $handler((int) $id, $values);
                } catch (\Throwable $e) {
                    return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                }
            }

            return $this->adminJson(ApiResponse::ok(['treeData' => $result?->treeData]));
        }

        $result = $handler((int) $ids, $values);

        return $this->adminJson(ApiResponse::ok(['treeData' => $result->treeData]));
    }

    #[Route('/save', name: 'save', methods: ['POST', 'PUT'])]
    public function saveAction(Request $request, SaveDataObjectHandler $handler): JsonResponse
    {
        $payload = DataObjectPayload::fromRequest($request);
        $result = $handler($request->request->getInt('id'), $payload);

        if ($payload->task === 'session' || $payload->task === 'scheduler') {
            return $this->adminJson(ApiResponse::ok());
        }

        if ($payload->task === 'publish' || $payload->task === 'unpublish') {
            return $this->adminJson(ApiResponse::ok([
                'general' => [
                    'modificationDate' => $result->modificationDate,
                    'versionDate' => $result->versionDate,
                    'versionCount' => $result->versionCount,
                ],
                'treeData' => $result->treeData,
            ]));
        }

        return $this->adminJson(ApiResponse::ok([
            'general' => [
                'modificationDate' => $result->modificationDate,
                'versionDate' => $result->versionDate,
                'versionCount' => $result->versionCount,
            ],
            'draft' => $result->draftData,
            'treeData' => $result->treeData,
        ]));
    }

    #[Route('/save-folder', name: 'savefolder', methods: ['PUT'])]
    public function saveFolderAction(SaveDataObjectFolderHandler $handler, Request $request): JsonResponse
    {
        $propertiesData = $request->request->has('properties')
            ? $this->decodeJson($request->request->get('properties'))
            : null;

        $handler(
            id: $request->request->getInt('id'),
            general: $this->decodeJson($request->request->get('general')),
            propertiesData: is_array($propertiesData) ? $propertiesData : null,
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/grid-proxy', name: 'gridproxy', methods: ['GET', 'POST', 'PUT'])]
    public function gridProxyAction(
        Request $request,
        DataObjectGridProxyHandler $handler,
        CsrfProtectionHandler $csrfProtection,
    ): JsonResponse {
        $csrfProtection->checkCsrfToken($request);

        $allParams = [...$request->request->all(), ...$request->query->all()];
        if (isset($allParams['context']) && $allParams['context']) {
            $allParams['context'] = json_decode($allParams['context'], true);
        } else {
            $allParams['context'] = [];
        }

        $result = $handler($allParams, $request->getLocale());
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
    ): RedirectResponse|Response
    {
        $object = $this->sessionService->getObject('object', $id);

        if ($object instanceof DataObject\Concrete) {
            $redirectUrl = $handler($object, ['context' => $this, ...$request->query->all()]);

            return $this->redirect($redirectUrl);
        }

        throw new NotFoundHttpException(sprintf('Expected an object of type "%s", got "%s"', DataObject\Concrete::class, get_debug_type($object)));
    }

}
