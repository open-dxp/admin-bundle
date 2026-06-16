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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObject;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddCollectionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddGroupsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddPropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\CreateCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\CreateGroupHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\CreateStoreHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteCollectionRelationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteGroupHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeletePropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteRelationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\EditStoreHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetCollectionRelationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetCollectionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetGroupsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetPageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetPropertiesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetRelationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetStoreTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\ListStoresHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SaveCollectionRelationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SaveRelationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SearchRelationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateGroupHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdatePropertyHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Security\SecurityHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/classificationstore', name: 'opendxp_admin_dataobject_classificationstore_')]
class ClassificationstoreController extends AdminAbstractController
{
    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/delete-collection', name: 'deletecollection', methods: ['DELETE'])]
    public function deleteCollectionAction(Request $request, DeleteCollectionHandler $handler): JsonResponse
    {
        $handler($request->request->getInt('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/delete-collection-relation', name: 'deletecollectionrelation', methods: ['DELETE'])]
    public function deleteCollectionRelationAction(Request $request, DeleteCollectionRelationHandler $handler): JsonResponse
    {
        $handler(
            colId: $request->request->getInt('colId'),
            groupId: $request->request->getInt('groupId'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/delete-relation', name: 'deleterelation', methods: ['DELETE'])]
    public function deleteRelationAction(Request $request, DeleteRelationHandler $handler): JsonResponse
    {
        $handler(
            keyId: $request->request->getInt('keyId'),
            groupId: $request->request->getInt('groupId'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/delete-group', name: 'deletegroup', methods: ['DELETE'])]
    public function deleteGroupAction(Request $request, DeleteGroupHandler $handler): JsonResponse
    {
        $handler($request->request->getInt('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/create-group', name: 'creategroup', methods: ['POST'])]
    public function createGroupAction(Request $request, CreateGroupHandler $handler): JsonResponse
    {
        $name = SecurityHelper::convertHtmlSpecialChars($request->request->get('name'));
        $result = $handler(
            name: $name,
            storeId: $request->request->getInt('storeId'),
        );

        if ($result->alreadyExists) {
            throw new BadRequestHttpException('classificationstore_error_group_exists_msg');
        }

        return $this->adminJson(ApiResponse::ok(['id' => $result->name]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/create-store', name: 'createstore', methods: ['POST'])]
    public function createStoreAction(Request $request, CreateStoreHandler $handler): JsonResponse
    {
        $name = SecurityHelper::convertHtmlSpecialChars($request->request->get('name'));
        $result = $handler($name);

        return $this->adminJson(ApiResponse::ok(['storeId' => $result->storeId]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/create-collection', name: 'createcollection', methods: ['POST'])]
    public function createCollectionAction(Request $request, CreateCollectionHandler $handler): JsonResponse
    {
        $name = SecurityHelper::convertHtmlSpecialChars($request->request->get('name'));
        $result = $handler(
            name: $name,
            storeId: $request->request->getInt('storeId'),
        );

        return $this->adminJson(ApiResponse::ok(['id' => $result->name]));
    }

    #[IsGranted(CorePermission::Objects->value)]
    #[Route('/collections', name: 'collectionsactionget', methods: ['GET'])]
    public function collectionsActionGet(
        Request $request,
        GetCollectionsHandler $handler,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $limit = null,
        #[MapQueryParameter] ?string $dir = null,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] bool $overrideSort = false,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $oid = null,
        #[MapQueryParameter] ?string $fieldname = null,
        #[MapQueryParameter] ?string $searchfilter = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $storeId = null,
        #[MapQueryParameter] ?string $filter = null,
    ): JsonResponse {
        $result = $handler(
            queryAll: $request->query->all(),
            limit: $limit ?? 15,
            start: $start,
            dir: $dir,
            overrideSort: $overrideSort,
            oid: $oid,
            fieldname: $fieldname,
            searchfilter: $searchfilter,
            storeId: $storeId,
            filter: $filter,
        );

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/collections', name: 'collections', methods: ['POST', 'PUT'])]
    public function collectionsAction(Request $request, UpdateCollectionHandler $handler): JsonResponse
    {
        if (!$request->request->has('data')) {
            throw new BadRequestHttpException();
        }

        $data = $this->decodeJson($request->request->get('data'));
        $result = $handler($data);

        return $this->adminJson(ApiResponse::ok(['data' => $result->item]));
    }

    #[IsGranted(CorePermission::Objects->value)]
    #[Route('/groups', name: 'groupsactionget', methods: ['GET'])]
    public function groupsActionGet(
        Request $request,
        GetGroupsHandler $handler,
        #[MapQueryParameter] ?string $dir = null,
        #[MapQueryParameter] ?string $sort = null,
        #[MapQueryParameter] int $limit = 0,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] bool $overrideSort = false,
        #[MapQueryParameter] ?string $searchfilter = null,
        #[MapQueryParameter] int $storeId = 0,
        #[MapQueryParameter] ?string $filter = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $oid = null,
        #[MapQueryParameter] ?string $fieldname = null,
    ): JsonResponse {
        $result = $handler(
            queryAll: $request->query->all(),
            limit: $limit ?: 15,
            start: $start,
            dir: $dir,
            sort: $sort,
            overrideSort: $overrideSort,
            searchfilter: $searchfilter,
            storeId: $storeId,
            filter: $filter,
            oid: $oid,
            fieldname: $fieldname,
        );

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/groups', name: 'groupsaction', methods: ['POST', 'PUT'])]
    public function groupsAction(Request $request, UpdateGroupHandler $handler): JsonResponse
    {
        if (!$request->request->has('data')) {
            throw new BadRequestHttpException();
        }

        $data = $this->decodeJson($request->request->get('data'));
        $result = $handler($data);

        return $this->adminJson(ApiResponse::ok(['data' => $result->item]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/collection-relations', name: 'collectionrelationsget', methods: ['GET'])]
    public function collectionRelationsGetAction(
        Request $request,
        GetCollectionRelationsHandler $handler,
        #[MapQueryParameter] ?string $dir = null,
        #[MapQueryParameter] bool $overrideSort = false,
        #[MapQueryParameter] int $limit = 0,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] ?string $filter = null,
        #[MapQueryParameter] int $colId = 0,
    ): JsonResponse {
        $result = $handler(
            queryAll: $request->query->all(),
            limit: $limit ?: 15,
            start: $start,
            dir: $dir,
            overrideSort: $overrideSort,
            filter: $filter,
            colId: $colId,
        );

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/collection-relations', name: 'collectionrelations', methods: ['POST', 'PUT'])]
    public function collectionRelationsAction(Request $request, SaveCollectionRelationsHandler $handler): JsonResponse
    {
        if (!$request->request->has('data')) {
            throw new BadRequestHttpException();
        }

        $data = $this->decodeJson($request->request->get('data'));
        $result = $handler($data);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/list-stores', name: 'liststores', methods: ['GET'])]
    public function listStoresAction(ListStoresHandler $handler): JsonResponse
    {
        $result = $handler();

        return $this->adminJson($result->storeConfigs);
    }

    #[Route('/search-relations', name: 'searchrelations', methods: ['GET'])]
    public function searchRelationsAction(
        Request $request,
        SearchRelationsHandler $handler,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $storeId = null,
        #[MapQueryParameter] ?string $dir = null,
        #[MapQueryParameter] bool $overrideSort = false,
        #[MapQueryParameter] int $limit = 0,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] ?string $filter = null,
        #[MapQueryParameter] ?string $searchfilter = null,
    ): JsonResponse {
        $result = $handler(
            queryAll: $request->query->all(),
            storeId: $storeId,
            limit: $limit ?: 15,
            start: $start,
            dir: $dir,
            overrideSort: $overrideSort,
            filter: $filter,
            searchfilter: $searchfilter,
        );

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[Route('/relations', name: 'relationsactionget', methods: ['GET'])]
    public function relationsActionGet(
        Request $request,
        GetRelationsHandler $handler,
        #[MapQueryParameter] ?string $relationIds = null,
        #[MapQueryParameter] ?string $dir = null,
        #[MapQueryParameter] bool $overrideSort = false,
        #[MapQueryParameter] int $limit = 0,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] ?string $filter = null,
        #[MapQueryParameter] ?string $groupId = null,
    ): JsonResponse {
        $result = $handler(
            queryAll: $request->query->all(),
            relationIds: $relationIds,
            limit: $limit ?: 15,
            start: $start,
            dir: $dir,
            overrideSort: $overrideSort,
            filter: $filter,
            groupId: $groupId,
        );

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/relations', name: 'relations', methods: ['POST', 'PUT'])]
    public function relationsAction(Request $request, SaveRelationHandler $handler): JsonResponse
    {
        if (!$request->request->has('data')) {
            throw new BadRequestHttpException();
        }

        $data = $this->decodeJson($request->request->get('data'));
        $result = $handler($data);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data]));
    }

    #[IsGranted(CorePermission::Objects->value)]
    #[Route('/add-collections', name: 'addcollections', methods: ['POST'])]
    public function addCollectionsAction(Request $request, AddCollectionsHandler $handler): JsonResponse
    {
        $ids = $this->decodeJson($request->request->get('collectionIds'));
        $result = $handler(
            ids: $ids ?: [],
            oid: $request->request->getInt('oid'),
            fieldname: $request->request->get('fieldname') ?? '',
        );

        return $this->adminJson($result->data);
    }

    #[IsGranted(CorePermission::Objects->value)]
    #[Route('/add-groups', name: 'addgroups', methods: ['POST'])]
    public function addGroupsAction(Request $request, AddGroupsHandler $handler): JsonResponse
    {
        $ids = $this->decodeJson($request->request->get('groupIds'));
        $result = $handler(
            ids: $ids,
            oid: $request->request->getInt('oid'),
            fieldname: $request->request->get('fieldname'),
        );

        return $this->adminJson($result->data);
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/properties', name: 'propertiesget', methods: ['GET'])]
    public function propertiesGetAction(
        Request $request,
        GetPropertiesHandler $handler,
        #[MapQueryParameter] int $storeId = 0,
        #[MapQueryParameter] ?string $frameName = null,
        #[MapQueryParameter] ?string $dir = null,
        #[MapQueryParameter] bool $overrideSort = false,
        #[MapQueryParameter] int $limit = 0,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] ?string $groupIds = null,
        #[MapQueryParameter] ?string $keyIds = null,
        #[MapQueryParameter] ?string $searchfilter = null,
        #[MapQueryParameter] ?string $filter = null,
    ): JsonResponse {
        $result = $handler(
            queryAll: $request->query->all(),
            storeId: $storeId,
            frameName: $frameName,
            limit: $limit ?: 15,
            start: $start,
            dir: $dir,
            overrideSort: $overrideSort,
            groupIds: $groupIds,
            keyIds: $keyIds,
            searchfilter: $searchfilter,
            filter: $filter,
        );

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/properties', name: 'properties', methods: ['POST', 'PUT'])]
    public function propertiesAction(Request $request, UpdatePropertyHandler $handler): JsonResponse
    {
        if (!$request->request->has('data')) {
            throw new BadRequestHttpException();
        }

        $data = $this->decodeJson($request->request->get('data'));
        $result = $handler($data);

        return $this->adminJson(ApiResponse::ok(['data' => $result->item]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/add-property', name: 'addproperty', methods: ['POST'])]
    public function addPropertyAction(Request $request, AddPropertyHandler $handler): JsonResponse
    {
        $result = $handler(
            name: $request->request->get('name'),
            storeId: $request->request->getInt('storeId'),
        );

        return $this->adminJson(ApiResponse::ok(['id' => $result->name]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/delete-property', name: 'deleteproperty', methods: ['DELETE'])]
    public function deletePropertyAction(Request $request, DeletePropertyHandler $handler): JsonResponse
    {
        $handler($request->request->getInt('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/edit-store', name: 'editstore', methods: ['PUT'])]
    public function editStoreAction(Request $request, EditStoreHandler $handler): JsonResponse
    {
        $id = $request->request->getInt('id');
        $data = json_decode($request->request->get('data'), true);

        $handler(
            id: $id,
            name: $data['name'],
            description: $data['description'],
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/storetree', name: 'storetree', methods: ['GET'])]
    public function storetreeAction(GetStoreTreeHandler $handler): JsonResponse
    {
        $result = $handler();

        return $this->adminJson($result->items);
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/get-page', name: 'getpage', methods: ['GET'])]
    public function getPageAction(
        GetPageHandler $handler,
        #[MapQueryParameter] ?string $table = null,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] int $storeId = 0,
        #[MapQueryParameter] int $pageSize = 0,
        #[MapQueryParameter] ?string $sortKey = null,
        #[MapQueryParameter] ?string $sortDir = null,
    ): JsonResponse {
        $result = $handler(
            table: $table,
            id: $id,
            storeId: $storeId,
            pageSize: $pageSize,
            sortKey: $sortKey,
            sortDir: $sortDir,
        );

        return $this->adminJson(ApiResponse::ok(['page' => $result->page]));
    }

}
