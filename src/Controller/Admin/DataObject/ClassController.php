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
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\AddClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkCommitHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkExportPrepareHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkImportHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DeleteClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DeleteSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DoBulkExportHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\ExportClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetAssetTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassBulkExportListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassDefinitionForColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassIconsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetDocumentTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsUsagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetTextLayoutPreviewHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetVideoAllowedTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\ImportClassHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveClassDefinitionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveSelectOptionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SuggestClassIdentifierHandler;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
    public function getTreeAction(
        GetClassTreeHandler $handler,
        #[MapQueryParameter] ?string $createAllowed = null,
        #[MapQueryParameter] ?string $withId = null,
        #[MapQueryParameter] ?string $useTitle = null,
        #[MapQueryParameter] ?string $grouped = null,
    ): JsonResponse {
        try {
            $this->checkPermission('objects');
        } catch (AccessDeniedHttpException) {
            Logger::log('[Startup] Object types are not loaded as "objects" permission is missing');

            return $this->adminJson([]);
        }

        return $this->adminJson($handler(
            createAllowed: (bool) $createAllowed,
            withId: (bool) $withId,
            useTitle: (bool) $useTitle,
            grouped: (bool) $grouped,
        )->nodes);
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/get', name: 'get', methods: ['GET'])]
    public function getAction(
        GetClassHandler $handler,
        #[MapQueryParameter] ?string $id = null,
    ): JsonResponse {
        return $this->adminJson($handler($id)->classData);
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/add', name: 'add', methods: ['POST'])]
    public function addAction(Request $request, AddClassHandler $handler): JsonResponse
    {
        $result = $handler(
            className: $request->request->get('className'),
            classId: $request->request->get('classIdentifier'),
        );

        return $this->adminJson(ApiResponse::ok(['id' => $result->id]));
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteAction(Request $request, DeleteClassHandler $handler): Response
    {
        $handler($request->request->get('id'));

        return new Response();
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/save', name: 'save', methods: ['PUT'])]
    public function saveAction(Request $request, SaveClassDefinitionHandler $handler): JsonResponse
    {
        $result = $handler(
            id: $request->request->get('id'),
            configuration: $this->decodeJson($request->request->get('configuration')),
            values: $this->decodeJson($request->request->get('values')),
        );

        return $this->adminJson(ApiResponse::ok(['class' => $result->class]));
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/import-class', name: 'importclass', methods: ['POST', 'PUT'])]
    public function importClassAction(
        Request $request,
        ImportClassHandler $handler,
        #[MapQueryParameter] ?string $id = null,
    ): Response {
        /** @var UploadedFile $file */
        $file = $request->files->get('Filedata');
        $handler($id, file_get_contents($file->getPathname()));

        $response = $this->adminJson(ApiResponse::ok());

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/export-class', name: 'exportclass', methods: ['GET'])]
    public function exportClassAction(
        ExportClassHandler $handler,
        #[MapQueryParameter] ?string $id = null,
    ): Response {
        $result = $handler($id);

        $response = new Response($result->json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename: "class_' . $result->className . '_export.json"');

        return $response;
    }

    #[Route('/get-class-definition-for-column-config', name: 'getclassdefinitionforcolumnconfig', methods: ['GET'])]
    public function getClassDefinitionForColumnConfigAction(
        GetClassDefinitionForColumnConfigHandler $handler,
        #[MapQueryParameter] ?string $id = null,
        #[MapQueryParameter] int $oid = 0,
    ): JsonResponse {
        return $this->adminJson($handler($id, $oid)->config);
    }

    /**
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/bulk-import', name: 'bulkimport', methods: ['POST'])]
    public function bulkImportAction(Request $request, BulkImportHandler $handler): JsonResponse
    {
        /** @var UploadedFile $uploadFile */
        $uploadFile = $request->files->get('Filedata');
        $result = $handler(file_get_contents($uploadFile->getPathname()));

        $response = $this->adminJson(ApiResponse::ok(['data' => $result->items]));
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    #[Route('/bulk-commit', name: 'bulkcommit', methods: ['POST'])]
    public function bulkCommitAction(Request $request, BulkCommitHandler $handler): JsonResponse
    {
        $handler(json_decode($request->request->get('data'), true));

        return $this->adminJson(ApiResponse::ok());
    }

    /**
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/bulk-export-prepare', name: 'bulkexportprepare', methods: ['POST'])]
    public function bulkExportPrepareAction(Request $request, BulkExportPrepareHandler $handler): Response
    {
        $handler($request->request->get('data'));

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

    #[Route('/get-select-options-usages', name: 'getselectoptionsusages', methods: [Request::METHOD_GET])]
    public function getSelectOptionsUsagesAction(
        GetSelectOptionsUsagesHandler $handler,
        #[MapQueryParameter(name: DataObject\SelectOptions\Config::PROPERTY_ID)] ?string $id = null,
    ): Response {
        return $this->adminJson($handler($id)->usages);
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/get-icons', name: 'geticons', methods: ['GET'])]
    public function getIconsAction(
        GetClassIconsHandler $handler,
        #[MapQueryParameter] ?string $classId = null,
        #[MapQueryParameter] ?string $type = null,
    ): Response {
        return $this->adminJson($handler($type, $classId)->icons);
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
    public function textLayoutPreviewAction(
        GetTextLayoutPreviewHandler $handler,
        #[MapQueryParameter] string $previewObject = '',
        #[MapQueryParameter] ?string $className = null,
        #[MapQueryParameter] ?string $renderingData = null,
        #[MapQueryParameter] ?string $renderingClass = null,
        #[MapQueryParameter] ?string $html = null,
    ): Response {
        $response = new Response($handler(
            objPath: $previewObject,
            className: $className,
            renderingData: $renderingData,
            renderingClass: $renderingClass,
            html: $html,
        )->content);
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
    #[Route('/select-options-get', name: 'selectoptionsget', methods: [Request::METHOD_GET])]
    public function selectOptionsGetAction(
        GetSelectOptionsHandler $handler,
        #[MapQueryParameter(name: DataObject\SelectOptions\Config::PROPERTY_ID)] ?string $id = null,
    ): JsonResponse {
        return $this->adminJson($handler($id)->data);
    }

    #[IsGranted(CorePermission::Selectoptions->value)]
    #[Route('/select-options-update', name: 'selectoptionsupdate', methods: [Request::METHOD_PUT, Request::METHOD_POST])]
    public function selectOptionsUpdateAction(
        Request $request,
        SaveSelectOptionsHandler $handler,
    ): JsonResponse {
        $result = $handler(
            id: $request->request->get(DataObject\SelectOptions\Config::PROPERTY_ID),
            task: $request->request->get('task', ''),
            group: $request->request->get(DataObject\SelectOptions\Config::PROPERTY_GROUP),
            useTraits: $request->request->get(DataObject\SelectOptions\Config::PROPERTY_USE_TRAITS, ''),
            implementsInterfaces: $request->request->get(DataObject\SelectOptions\Config::PROPERTY_IMPLEMENTS_INTERFACES, ''),
            selectOptionsData: $this->decodeJson($request->request->get(DataObject\SelectOptions\Config::PROPERTY_SELECT_OPTIONS, 'null')),
        );

        return $this->adminJson(ApiResponse::ok(['id' => $result->id]));
    }

    #[IsGranted(CorePermission::Selectoptions->value)]
    #[Route('/select-options-tree', name: 'selectoptionstree', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function selectOptionsTreeAction(
        GetSelectOptionsTreeHandler $handler,
        #[MapQueryParameter] int $grouped = 0,
    ): JsonResponse {
        return $this->adminJson($handler($grouped)->configurations);
    }

    #[IsGranted(CorePermission::Selectoptions->value)]
    #[Route('/select-options-delete', name: 'selectoptionsdelete', methods: [Request::METHOD_DELETE])]
    public function selectOptionsDeleteAction(
        Request $request,
        DeleteSelectOptionsHandler $handler,
    ): JsonResponse {
        $handler($request->request->get(DataObject\SelectOptions\Config::PROPERTY_ID));

        return $this->adminJson(ApiResponse::ok());
    }
}
