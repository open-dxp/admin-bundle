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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\DeleteFieldCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\ExportFieldCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\ExportFieldCollection\ExportFieldCollectionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollection\GetFieldCollectionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionList\GetFieldCollectionListPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionTree\GetFieldCollectionTreePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionUsagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionUsages\GetFieldCollectionUsagesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\ImportFieldCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\ImportFieldCollection\ImportFieldCollectionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\UpdateFieldCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\UpdateFieldCollection\UpdateFieldCollectionPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\StringIdBodyPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/class', name: 'opendxp_admin_dataobject_class_')]
#[IsGranted(CorePermission::Fieldcollections->value)]
class FieldCollectionController extends AdminAbstractController
{
    #[Route('/fieldcollection-get', name: 'fieldcollectionget', methods: ['GET'])]
    public function fieldcollectionGetAction(GetFieldCollectionHandler $getFieldCollection, GetFieldCollectionPayload $payload): JsonResponse
    {
        $result = $getFieldCollection($payload);
        $data = $result->data;
        $data['isWriteable'] = $result->isWriteable;

        return $this->adminJson($data);
    }

    #[Route('/fieldcollection-update', name: 'fieldcollectionupdate', methods: ['PUT', 'POST'])]
    public function fieldcollectionUpdateAction(UpdateFieldCollectionHandler $updateFieldCollection, UpdateFieldCollectionPayload $payload): JsonResponse
    {
        $fcDef = $updateFieldCollection($payload);

        return $this->adminJson(ApiResponse::ok(['id' => $fcDef->getKey()]));
    }

    #[Route('/fieldcollection-delete', name: 'fieldcollectiondelete', methods: ['DELETE'])]
    public function fieldcollectionDeleteAction(DeleteFieldCollectionHandler $deleteFieldCollection, StringIdBodyPayload $payload): JsonResponse
    {
        $deleteFieldCollection($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/fieldcollection-tree', name: 'fieldcollectiontree', methods: ['GET', 'POST'])]
    public function fieldcollectionTreeAction(GetFieldCollectionTreeHandler $getTree, GetFieldCollectionTreePayload $payload): JsonResponse
    {
        $result = $getTree($payload);

        if ($payload->forObjectEditor) {
            return $this->adminJson(['fieldcollections' => $result->definitions, 'layoutDefinitions' => $result->layoutDefinitions]);
        }

        return $this->adminJson($result->definitions);
    }

    #[Route('/fieldcollection-list', name: 'fieldcollectionlist', methods: ['GET'])]
    public function fieldcollectionListAction(GetFieldCollectionListHandler $getList, GetFieldCollectionListPayload $payload): JsonResponse
    {
        $result = $getList($payload);

        return $this->adminJson(['fieldcollections' => $result->fieldcollections]);
    }

    #[Route('/import-fieldcollection', name: 'importfieldcollection', methods: ['POST'])]
    public function importFieldcollectionAction(ImportFieldCollectionHandler $importFieldCollection, ImportFieldCollectionPayload $payload): Response
    {
        $importFieldCollection($payload);

        $response = $this->adminJson(ApiResponse::ok());
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/export-fieldcollection', name: 'exportfieldcollection', methods: ['GET'])]
    public function exportFieldcollectionAction(ExportFieldCollectionHandler $exportFieldCollection, ExportFieldCollectionPayload $payload): Response
    {
        $result = $exportFieldCollection($payload);

        $response = new Response($result->json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="fieldcollection_' . $result->key . '_export.json"');

        return $response;
    }

    #[Route('/get-fieldcollection-usages', name: 'getfieldcollectionusages', methods: ['GET'])]
    public function getFieldcollectionUsagesAction(GetFieldCollectionUsagesHandler $getUsages, GetFieldCollectionUsagesPayload $payload): Response
    {
        return $this->adminJson($getUsages($payload));
    }
}
