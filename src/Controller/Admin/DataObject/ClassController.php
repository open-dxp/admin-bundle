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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\AddClass\AddClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\AddClass\AddClassPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkCommit\BulkCommitHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkCommit\BulkCommitPayload;
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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassBulkExportList\GetClassBulkExportListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassDefinitionForColumnConfig\GetClassDefinitionForColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassDefinitionForColumnConfig\GetClassDefinitionForColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClass\GetClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassIcons\GetClassIconsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassIcons\GetClassIconsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClass\GetClassPayload;
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
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Logger;
use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
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
    #[AsHtmlContentTypeResponse]
    #[Route('/import-class', name: 'importclass', methods: ['POST', 'PUT'])]
    public function importClassAction(ImportClassPayload $payload, ImportClassHandler $handler): Response
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
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
    #[AsHtmlContentTypeResponse]
    #[Route('/bulk-import', name: 'bulkimport', methods: ['POST'])]
    public function bulkImportAction(BulkImportPayload $payload, BulkImportHandler $handler, Request $request): JsonResponse
    {
        $result = $handler($payload);

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($result): void {
            $session->set('class_bulk_import_file', $result->tmpFile);
        }, 'opendxp_objects');

        return $this->adminJson(ApiResponse::ok(['data' => $result->items]));
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
    public function bulkExportPrepareAction(BulkExportPreparePayload $payload, Request $request): Response
    {
        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($payload): void {
            $session->set('class_bulk_export_settings', $payload->data);
        }, 'opendxp_objects');

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
