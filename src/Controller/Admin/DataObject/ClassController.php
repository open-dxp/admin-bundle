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
use OpenDxp\Bundle\AdminBundle\Attribute\SessionGatewayAware;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\AddClass\AddClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\AddClass\AddClassPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkCommit\BulkCommitHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkCommit\BulkCommitPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkExportPrepare\BulkExportPrepareHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkExportPrepare\BulkExportPreparePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkImport\BulkImportHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkImport\BulkImportPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DeleteClass\DeleteClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DeleteClass\DeleteClassPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DeleteSelectOptions\DeleteSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DeleteSelectOptions\DeleteSelectOptionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DoBulkExport\DoBulkExportHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\ExportClass\ExportClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\ExportClass\ExportClassPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetAssetTypes\GetAssetTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClass\GetClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClass\GetClassPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassBulkExportList\GetClassBulkExportListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassDefinitionForColumnConfig\GetClassDefinitionForColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassDefinitionForColumnConfig\GetClassDefinitionForColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassIcons\GetClassIconsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassIcons\GetClassIconsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassTree\GetClassTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassTree\GetClassTreePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetDocumentTypes\GetDocumentTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptions\GetSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptions\GetSelectOptionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsTree\GetSelectOptionsTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsTree\GetSelectOptionsTreePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsUsages\GetSelectOptionsUsagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsUsages\GetSelectOptionsUsagesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetTextLayoutPreview\GetTextLayoutPreviewHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetTextLayoutPreview\GetTextLayoutPreviewPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetVideoAllowedTypes\GetVideoAllowedTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\ImportClass\ImportClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\ImportClass\ImportClassPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveClassDefinition\SaveClassDefinitionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveClassDefinition\SaveClassDefinitionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveSelectOptions\SaveSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveSelectOptions\SaveSelectOptionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SuggestClassIdentifier\SuggestClassIdentifierHandler;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\BulkOperationSessionGateway;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/class', name: 'opendxp_admin_dataobject_class_')]
class ClassController extends AdminAbstractController
{
    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/get-document-types', name: 'getdocumenttypes', methods: ['GET'])]
    public function getDocumentTypesAction(GetDocumentTypesHandler $handler): JsonResponse
    {
        return $this->apiJson($handler(), rootProperty: 'types');
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/get-asset-types', name: 'getassettypes', methods: ['GET'])]
    public function getAssetTypesAction(GetAssetTypesHandler $handler): JsonResponse
    {
        return $this->apiJson($handler(), rootProperty: 'types');
    }

    #[Route('/get-tree', name: 'gettree', methods: ['GET', 'POST'])]
    public function getTreeAction(GetClassTreePayload $payload, GetClassTreeHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload), rootProperty: 'nodes');
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/get', name: 'get', methods: ['GET'])]
    public function getAction(GetClassPayload $payload, GetClassHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload), rootProperty: 'classData');
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/add', name: 'add', methods: ['POST'])]
    public function addAction(AddClassPayload $payload, AddClassHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteAction(DeleteClassPayload $payload, DeleteClassHandler $handler): Response
    {
        $handler($payload);

        return new Response();
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/save', name: 'save', methods: ['PUT'])]
    public function saveAction(SaveClassDefinitionPayload $payload, SaveClassDefinitionHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[AsHtmlContentTypeResponse]
    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/import-class', name: 'importclass', methods: ['POST', 'PUT'])]
    public function importClassAction(ImportClassPayload $payload, ImportClassHandler $handler): Response
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/export-class', name: 'exportclass', methods: ['GET'])]
    public function exportClassAction(ExportClassPayload $payload, ExportClassHandler $handler): Response
    {
        $result = $handler($payload);

        $response = new Response($result->json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="class_' . $result->className . '_export.json"');

        return $response;
    }

    #[Route('/get-class-definition-for-column-config', name: 'getclassdefinitionforcolumnconfig', methods: ['GET'])]
    public function getClassDefinitionForColumnConfigAction(
        GetClassDefinitionForColumnConfigPayload $payload,
        GetClassDefinitionForColumnConfigHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'config');
    }

    /**
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    #[AsHtmlContentTypeResponse]
    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/bulk-import', name: 'bulkimport', methods: ['POST'])]
    #[SessionGatewayAware(BulkOperationSessionGateway::class)]
    public function bulkImportAction(BulkImportPayload $payload, BulkImportHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    /**
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    #[Route('/bulk-commit', name: 'bulkcommit', methods: ['POST'])]
    #[SessionGatewayAware(BulkOperationSessionGateway::class)]
    public function bulkCommitAction(BulkCommitPayload $payload, BulkCommitHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    /**
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/bulk-export-prepare', name: 'bulkexportprepare', methods: ['POST'])]
    #[SessionGatewayAware(BulkOperationSessionGateway::class)]
    public function bulkExportPrepareAction(BulkExportPreparePayload $payload, BulkExportPrepareHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/bulk-export', name: 'bulkexport', methods: ['GET'])]
    public function bulkExportAction(GetClassBulkExportListHandler $handler): JsonResponse
    {
        return $this->apiJson($handler());
    }

    #[Route('/do-bulk-export', name: 'dobulkexport', methods: ['GET'])]
    #[SessionGatewayAware(BulkOperationSessionGateway::class)]
    public function doBulkExportAction(DoBulkExportHandler $handler): Response
    {
        $result = $handler();

        $response = new Response($result->json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="bulk_export.json"');

        return $response;
    }

    #[Route('/get-select-options-usages', name: 'getselectoptionsusages', methods: ['GET'])]
    public function getSelectOptionsUsagesAction(
        GetSelectOptionsUsagesPayload $payload,
        GetSelectOptionsUsagesHandler $handler,
    ): Response {
        return $this->apiJson($handler($payload), rootProperty: 'usages');
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/get-icons', name: 'geticons', methods: ['GET'])]
    public function getIconsAction(GetClassIconsPayload $payload, GetClassIconsHandler $handler): Response
    {
        return $this->apiJson($handler($payload), rootProperty: 'icons');
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/suggest-class-identifier', name: 'suggestclassidentifier')]
    public function suggestClassIdentifierAction(SuggestClassIdentifierHandler $handler): JsonResponse
    {
        return $this->apiJson($handler(), envelope: false);
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/text-layout-preview', name: 'textlayoutpreview', methods: ['GET'])]
    public function textLayoutPreviewAction(GetTextLayoutPreviewPayload $payload, GetTextLayoutPreviewHandler $handler): Response
    {
        $response = new Response($handler($payload)->content);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/video-supported-types', name: 'videosupportedTypestypes', methods: ['GET'])]
    public function videoAllowedTypesAction(GetVideoAllowedTypesHandler $handler): Response
    {
        return $this->apiJson($handler(), rootProperty: 'types');
    }

    #[IsGranted(CorePermission::Selectoptions->value)]
    #[Route('/select-options-get', name: 'selectoptionsget', methods: ['GET'])]
    public function selectOptionsGetAction(GetSelectOptionsPayload $payload, GetSelectOptionsHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[IsGranted(CorePermission::Selectoptions->value)]
    #[Route('/select-options-update', name: 'selectoptionsupdate', methods: ['PUT', 'POST'])]
    public function selectOptionsUpdateAction(SaveSelectOptionsPayload $payload, SaveSelectOptionsHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Selectoptions->value)]
    #[Route('/select-options-tree', name: 'selectoptionstree', methods: ['GET', 'POST'])]
    public function selectOptionsTreeAction(GetSelectOptionsTreePayload $payload, GetSelectOptionsTreeHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload), rootProperty: 'configurations');
    }

    #[IsGranted(CorePermission::Selectoptions->value)]
    #[Route('/select-options-delete', name: 'selectoptionsdelete', methods: ['DELETE'])]
    public function selectOptionsDeleteAction(DeleteSelectOptionsPayload $payload, DeleteSelectOptionsHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }
}
