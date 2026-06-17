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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\AddClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\AddClassPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkCommitHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkCommitPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkExportPrepareHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkExportPreparePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkImportHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkImportPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DeleteClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DeleteClassPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DeleteSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DeleteSelectOptionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DoBulkExportHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\ExportClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\ExportClassPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetAssetTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassBulkExportListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassDefinitionForColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassDefinitionForColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassIconsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassIconsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassTreePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetDocumentTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsTreePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsUsagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsUsagesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetTextLayoutPreviewHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetTextLayoutPreviewPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetVideoAllowedTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\ImportClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\ImportClassPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveClassDefinitionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveClassDefinitionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveSelectOptionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SuggestClassIdentifierHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        return $this->adminJson($handler()->types);
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/get-asset-types', name: 'getassettypes', methods: ['GET'])]
    public function getAssetTypesAction(GetAssetTypesHandler $handler): JsonResponse
    {
        return $this->adminJson($handler()->types);
    }

    #[Route('/get-tree', name: 'gettree', methods: ['GET', 'POST'])]
    public function getTreeAction(GetClassTreePayload $payload, GetClassTreeHandler $handler): JsonResponse
    {
        try {
            $this->checkPermission('objects');
        } catch (AccessDeniedHttpException) {
            Logger::log('[Startup] Object types are not loaded as "objects" permission is missing');

            return $this->adminJson([]);
        }

        return $this->adminJson($handler($payload)->nodes);
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/get', name: 'get', methods: ['GET'])]
    public function getAction(GetClassPayload $payload, GetClassHandler $handler): JsonResponse
    {
        return $this->adminJson($handler($payload)->classData);
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/add', name: 'add', methods: ['POST'])]
    public function addAction(AddClassPayload $payload, AddClassHandler $handler): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok(['id' => $handler($payload)->id]));
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
        return $this->adminJson(ApiResponse::ok(['class' => $handler($payload)->class]));
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/import-class', name: 'importclass', methods: ['POST', 'PUT'])]
    public function importClassAction(ImportClassPayload $payload, ImportClassHandler $handler): Response
    {
        $handler($payload);

        $response = $this->adminJson(ApiResponse::ok());

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/export-class', name: 'exportclass', methods: ['GET'])]
    public function exportClassAction(ExportClassPayload $payload, ExportClassHandler $handler): Response
    {
        $result = $handler($payload);

        $response = new Response($result->json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename: "class_' . $result->className . '_export.json"');

        return $response;
    }

    #[Route('/get-class-definition-for-column-config', name: 'getclassdefinitionforcolumnconfig', methods: ['GET'])]
    public function getClassDefinitionForColumnConfigAction(
        GetClassDefinitionForColumnConfigPayload $payload,
        GetClassDefinitionForColumnConfigHandler $handler,
    ): JsonResponse {
        return $this->adminJson($handler($payload)->config);
    }

    /**
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/bulk-import', name: 'bulkimport', methods: ['POST'])]
    public function bulkImportAction(BulkImportPayload $payload, BulkImportHandler $handler): JsonResponse
    {
        $response = $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->items]));
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    #[Route('/bulk-commit', name: 'bulkcommit', methods: ['POST'])]
    public function bulkCommitAction(BulkCommitPayload $payload, BulkCommitHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    /**
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/bulk-export-prepare', name: 'bulkexportprepare', methods: ['POST'])]
    public function bulkExportPrepareAction(BulkExportPreparePayload $payload, BulkExportPrepareHandler $handler): Response
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/bulk-export', name: 'bulkexport', methods: ['GET'])]
    public function bulkExportAction(GetClassBulkExportListHandler $handler): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok(['data' => $handler()->data]));
    }

    #[Route('/do-bulk-export', name: 'dobulkexport', methods: ['GET'])]
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
        return $this->adminJson($handler($payload)->usages);
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/get-icons', name: 'geticons', methods: ['GET'])]
    public function getIconsAction(GetClassIconsPayload $payload, GetClassIconsHandler $handler): Response
    {
        return $this->adminJson($handler($payload)->icons);
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/suggest-class-identifier', name: 'suggestclassidentifier')]
    public function suggestClassIdentifierAction(SuggestClassIdentifierHandler $handler): Response
    {
        $result = $handler();

        return $this->adminJson([
            'suggestedIdentifier' => $result->suggestedIdentifier,
            'existingIds' => $result->existingIds,
        ]);
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
        return $this->adminJson($handler()->types);
    }

    #[IsGranted(CorePermission::Selectoptions->value)]
    #[Route('/select-options-get', name: 'selectoptionsget', methods: ['GET'])]
    public function selectOptionsGetAction(GetSelectOptionsPayload $payload, GetSelectOptionsHandler $handler): JsonResponse
    {
        return $this->adminJson($handler($payload)->data);
    }

    #[IsGranted(CorePermission::Selectoptions->value)]
    #[Route('/select-options-update', name: 'selectoptionsupdate', methods: ['PUT', 'POST'])]
    public function selectOptionsUpdateAction(SaveSelectOptionsPayload $payload, SaveSelectOptionsHandler $handler): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok(['id' => $handler($payload)->id]));
    }

    #[IsGranted(CorePermission::Selectoptions->value)]
    #[Route('/select-options-tree', name: 'selectoptionstree', methods: ['GET', 'POST'])]
    public function selectOptionsTreeAction(GetSelectOptionsTreePayload $payload, GetSelectOptionsTreeHandler $handler): JsonResponse
    {
        return $this->adminJson($handler($payload)->configurations);
    }

    #[IsGranted(CorePermission::Selectoptions->value)]
    #[Route('/select-options-delete', name: 'selectoptionsdelete', methods: ['DELETE'])]
    public function selectOptionsDeleteAction(DeleteSelectOptionsPayload $payload, DeleteSelectOptionsHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }
}
