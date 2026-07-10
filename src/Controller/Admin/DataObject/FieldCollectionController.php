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

use OpenDxp\Bundle\AdminBundle\Attribute\AsHtmlContentTypeResponse;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\DeleteFieldCollection\DeleteFieldCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\ExportFieldCollection\ExportFieldCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\ExportFieldCollection\ExportFieldCollectionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollection\GetFieldCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollection\GetFieldCollectionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionList\GetFieldCollectionListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionList\GetFieldCollectionListPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionTree\GetFieldCollectionTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionTree\GetFieldCollectionTreePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionUsages\GetFieldCollectionUsagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionUsages\GetFieldCollectionUsagesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\ImportFieldCollection\ImportFieldCollectionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\ImportFieldCollection\ImportFieldCollectionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\UpdateFieldCollection\UpdateFieldCollectionHandler;
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
class FieldCollectionController extends AdminAbstractController
{
    #[Route('/fieldcollection-get', name: 'fieldcollectionget', methods: ['GET'])]
    public function fieldcollectionGetAction(GetFieldCollectionHandler $handler, GetFieldCollectionPayload $payload): JsonResponse
    {
        $result = $handler($payload);
        $data = $result->data;
        $data['isWriteable'] = $result->isWriteable;

        return $this->adminJson($data);
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/fieldcollection-update', name: 'fieldcollectionupdate', methods: ['PUT', 'POST'])]
    public function fieldcollectionUpdateAction(UpdateFieldCollectionHandler $handler, UpdateFieldCollectionPayload $payload): JsonResponse
    {
        $fcDef = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['id' => $fcDef->getKey()]));
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[IsGranted(CorePermission::Fieldcollections->value)]
    #[Route('/fieldcollection-delete', name: 'fieldcollectiondelete', methods: ['DELETE'])]
    public function fieldcollectionDeleteAction(DeleteFieldCollectionHandler $handler, StringIdBodyPayload $payload): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/fieldcollection-tree', name: 'fieldcollectiontree', methods: ['GET', 'POST'])]
    public function fieldcollectionTreeAction(GetFieldCollectionTreeHandler $handler, GetFieldCollectionTreePayload $payload): JsonResponse
    {
        $result = $handler($payload);

        if ($payload->forObjectEditor) {
            return $this->adminJson(['fieldcollections' => $result->definitions, 'layoutDefinitions' => $result->layoutDefinitions]);
        }

        return $this->adminJson($result->definitions);
    }

    #[Route('/fieldcollection-list', name: 'fieldcollectionlist', methods: ['GET'])]
    public function fieldcollectionListAction(GetFieldCollectionListHandler $handler, GetFieldCollectionListPayload $payload): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(['fieldcollections' => $result->fieldcollections]);
    }

    #[IsGranted(CorePermission::Fieldcollections->value)]
    #[AsHtmlContentTypeResponse]
    #[Route('/import-fieldcollection', name: 'importfieldcollection', methods: ['POST'])]
    public function importFieldcollectionAction(ImportFieldCollectionHandler $handler, ImportFieldCollectionPayload $payload): Response
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Fieldcollections->value)]
    #[Route('/export-fieldcollection', name: 'exportfieldcollection', methods: ['GET'])]
    public function exportFieldcollectionAction(ExportFieldCollectionHandler $handler, ExportFieldCollectionPayload $payload): Response
    {
        $result = $handler($payload);

        $response = new Response($result->json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="fieldcollection_' . $result->key . '_export.json"');

        return $response;
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/get-fieldcollection-usages', name: 'getfieldcollectionusages', methods: ['GET'])]
    public function getFieldcollectionUsagesAction(GetFieldCollectionUsagesHandler $handler, GetFieldCollectionUsagesPayload $payload): Response
    {
        return $this->adminJson($handler($payload));
    }
}
