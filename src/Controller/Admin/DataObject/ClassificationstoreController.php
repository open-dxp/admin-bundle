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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddCollections\AddCollectionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddCollections\AddCollectionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddGroups\AddGroupsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddGroups\AddGroupsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddProperty\AddPropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddProperty\AddPropertyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\CreateCollection\CreateCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\CreateCollection\CreateCollectionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\CreateGroup\CreateGroupHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\CreateGroup\CreateGroupPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\CreateStore\CreateStoreHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\CreateStore\CreateStorePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteCollection\DeleteCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteCollection\DeleteCollectionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteCollectionRelation\DeleteCollectionRelationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteCollectionRelation\DeleteCollectionRelationPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteGroup\DeleteGroupHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteGroup\DeleteGroupPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteProperty\DeletePropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteProperty\DeletePropertyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteRelation\DeleteRelationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteRelation\DeleteRelationPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\EditStore\EditStoreHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\EditStore\EditStorePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetCollectionRelations\GetCollectionRelationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetCollectionRelations\GetCollectionRelationsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetCollections\GetCollectionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetCollections\GetCollectionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetGroups\GetGroupsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetGroups\GetGroupsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetPage\GetPageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetPage\GetPagePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetProperties\GetPropertiesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetProperties\GetPropertiesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetRelations\GetRelationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetRelations\GetRelationsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetStoreTree\GetStoreTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\ListStores\ListStoresHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SaveCollectionRelations\SaveCollectionRelationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SaveCollectionRelations\SaveCollectionRelationsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SaveRelation\SaveRelationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SaveRelation\SaveRelationPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SearchRelations\SearchRelationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SearchRelations\SearchRelationsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateCollection\UpdateCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateCollection\UpdateCollectionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateGroup\UpdateGroupHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateGroup\UpdateGroupPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateProperty\UpdatePropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateProperty\UpdatePropertyPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function deleteCollectionAction(DeleteCollectionPayload $payload, DeleteCollectionHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/delete-collection-relation', name: 'deletecollectionrelation', methods: ['DELETE'])]
    public function deleteCollectionRelationAction(DeleteCollectionRelationPayload $payload, DeleteCollectionRelationHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/delete-relation', name: 'deleterelation', methods: ['DELETE'])]
    public function deleteRelationAction(DeleteRelationPayload $payload, DeleteRelationHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/delete-group', name: 'deletegroup', methods: ['DELETE'])]
    public function deleteGroupAction(DeleteGroupPayload $payload, DeleteGroupHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/create-group', name: 'creategroup', methods: ['POST'])]
    public function createGroupAction(CreateGroupPayload $payload, CreateGroupHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        if ($result->alreadyExists) {
            throw new BadRequestHttpException('classificationstore_error_group_exists_msg');
        }

        return $this->adminJson(ApiResponse::ok(['id' => $result->name]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/create-store', name: 'createstore', methods: ['POST'])]
    public function createStoreAction(CreateStorePayload $payload, CreateStoreHandler $handler): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok(['storeId' => $handler($payload)->storeId]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/create-collection', name: 'createcollection', methods: ['POST'])]
    public function createCollectionAction(CreateCollectionPayload $payload, CreateCollectionHandler $handler): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok(['id' => $handler($payload)->name]));
    }

    #[IsGranted(CorePermission::Objects->value)]
    #[Route('/collections', name: 'collectionsactionget', methods: ['GET'])]
    public function collectionsActionGet(GetCollectionsPayload $payload, GetCollectionsHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/collections', name: 'collections', methods: ['POST', 'PUT'])]
    public function collectionsAction(UpdateCollectionPayload $payload, UpdateCollectionHandler $handler): JsonResponse
    {
        if (!$payload->hasData) {
            throw new BadRequestHttpException();
        }

        return $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->item]));
    }

    #[IsGranted(CorePermission::Objects->value)]
    #[Route('/groups', name: 'groupsactionget', methods: ['GET'])]
    public function groupsActionGet(GetGroupsPayload $payload, GetGroupsHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/groups', name: 'groupsaction', methods: ['POST', 'PUT'])]
    public function groupsAction(UpdateGroupPayload $payload, UpdateGroupHandler $handler): JsonResponse
    {
        if (!$payload->hasData) {
            throw new BadRequestHttpException();
        }

        return $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->item]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/collection-relations', name: 'collectionrelationsget', methods: ['GET'])]
    public function collectionRelationsGetAction(GetCollectionRelationsPayload $payload, GetCollectionRelationsHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/collection-relations', name: 'collectionrelations', methods: ['POST', 'PUT'])]
    public function collectionRelationsAction(SaveCollectionRelationsPayload $payload, SaveCollectionRelationsHandler $handler): JsonResponse
    {
        if (!$payload->hasData) {
            throw new BadRequestHttpException();
        }

        return $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->data]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/list-stores', name: 'liststores', methods: ['GET'])]
    public function listStoresAction(ListStoresHandler $handler): JsonResponse
    {
        return $this->adminJson($handler()->storeConfigs);
    }

    #[Route('/search-relations', name: 'searchrelations', methods: ['GET'])]
    public function searchRelationsAction(SearchRelationsPayload $payload, SearchRelationsHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[Route('/relations', name: 'relationsactionget', methods: ['GET'])]
    public function relationsActionGet(GetRelationsPayload $payload, GetRelationsHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/relations', name: 'relations', methods: ['POST', 'PUT'])]
    public function relationsAction(SaveRelationPayload $payload, SaveRelationHandler $handler): JsonResponse
    {
        if (!$payload->hasData) {
            throw new BadRequestHttpException();
        }

        return $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->data]));
    }

    #[IsGranted(CorePermission::Objects->value)]
    #[Route('/add-collections', name: 'addcollections', methods: ['POST'])]
    public function addCollectionsAction(AddCollectionsPayload $payload, AddCollectionsHandler $handler): JsonResponse
    {
        return $this->adminJson($handler($payload)->data);
    }

    #[IsGranted(CorePermission::Objects->value)]
    #[Route('/add-groups', name: 'addgroups', methods: ['POST'])]
    public function addGroupsAction(AddGroupsPayload $payload, AddGroupsHandler $handler): JsonResponse
    {
        return $this->adminJson($handler($payload)->data);
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/properties', name: 'propertiesget', methods: ['GET'])]
    public function propertiesGetAction(GetPropertiesPayload $payload, GetPropertiesHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/properties', name: 'properties', methods: ['POST', 'PUT'])]
    public function propertiesAction(UpdatePropertyPayload $payload, UpdatePropertyHandler $handler): JsonResponse
    {
        if (!$payload->hasData) {
            throw new BadRequestHttpException();
        }

        return $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->item]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/add-property', name: 'addproperty', methods: ['POST'])]
    public function addPropertyAction(AddPropertyPayload $payload, AddPropertyHandler $handler): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok(['id' => $handler($payload)->name]));
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/delete-property', name: 'deleteproperty', methods: ['DELETE'])]
    public function deletePropertyAction(DeletePropertyPayload $payload, DeletePropertyHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/edit-store', name: 'editstore', methods: ['PUT'])]
    public function editStoreAction(EditStorePayload $payload, EditStoreHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/storetree', name: 'storetree', methods: ['GET'])]
    public function storetreeAction(GetStoreTreeHandler $handler): JsonResponse
    {
        return $this->adminJson($handler()->items);
    }

    #[IsGranted(CorePermission::Classificationstore->value)]
    #[Route('/get-page', name: 'getpage', methods: ['GET'])]
    public function getPageAction(GetPagePayload $payload, GetPageHandler $handler): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok(['page' => $handler($payload)->page]));
    }
}
