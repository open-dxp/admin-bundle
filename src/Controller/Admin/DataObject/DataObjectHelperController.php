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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ApplyGridConfigToAllHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ApplyGridConfigToAllPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DeleteDataObjectGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DeleteDataObjectGridColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DeleteGridColumnConfig\DeleteGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DeleteGridColumnConfig\DeleteGridColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DoDataObjectExportHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DoDataObjectExportPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ExecuteBatchHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ExecuteBatchPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetAvailableVisibleFieldsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetAvailableVisibleFieldsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetBatchJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetBatchJobsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetExportConfigsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetExportConfigsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetExportJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetExportJobsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetGridColumnConfig\GetGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetGridColumnConfig\GetGridColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ImportUploadHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ImportUploadPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\LoadObjectDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\LoadObjectDataPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\MarkDataObjectGridConfigFavouriteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\MarkDataObjectGridConfigFavouritePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\PrepareHelperColumnConfigsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\PrepareHelperColumnConfigsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\SaveDataObjectGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\SaveDataObjectGridColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/object-helper', name: 'opendxp_admin_dataobject_dataobjecthelper_')]
class DataObjectHelperController extends AdminAbstractController
{
    public function __construct(private readonly GridExportService $gridExportService) {}

    #[Route('/load-object-data', name: 'loadobjectdata', methods: ['GET'])]
    public function loadObjectDataAction(
        LoadObjectDataPayload $payload,
        LoadObjectDataHandler $handler,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['fields' => $handler($payload)]));
    }

    #[Route('/get-export-configs', name: 'getexportconfigs', methods: ['GET'])]
    public function getExportConfigsAction(
        GetExportConfigsPayload $payload,
        GetExportConfigsHandler $getExportConfigs,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $getExportConfigs($payload)]));
    }

    #[Route('/grid-delete-column-config', name: 'griddeletecolumnconfig', methods: ['DELETE'])]
    public function gridDeleteColumnConfigAction(
        DeleteGridColumnConfigPayload $payload,
        DeleteGridColumnConfigHandler $handler,
        DeleteDataObjectGridColumnConfigHandler $deleteGridColumnConfig,
    ): JsonResponse {
        $deleteGridColumnConfig(new DeleteDataObjectGridColumnConfigPayload(gridConfigId: (int) $payload->gridConfigId));

        return $this->adminJson($handler($payload));
    }

    #[Route('/grid-get-column-config', name: 'gridgetcolumnconfig', methods: ['GET'])]
    public function gridGetColumnConfigAction(
        GetGridColumnConfigPayload $payload,
        GetGridColumnConfigHandler $handler,
    ): JsonResponse {
        return $this->adminJson($handler($payload));
    }

    #[Route('/prepare-helper-column-configs', name: 'preparehelpercolumnconfigs', methods: ['POST'])]
    public function prepareHelperColumnConfigs(
        PrepareHelperColumnConfigsPayload $payload,
        PrepareHelperColumnConfigsHandler $prepareHelperColumns,
    ): JsonResponse {
        $result = $prepareHelperColumns($payload);

        $payload->helperColumnsBag->set('helpercolumns', $result['helperColumns']);

        return $this->adminJson(ApiResponse::ok(['columns' => $result['newData']]));
    }

    #[Route('/grid-config-apply-to-all', name: 'gridconfigapplytoall', methods: ['POST'])]
    public function gridConfigApplyToAllAction(
        ApplyGridConfigToAllPayload $payload,
        ApplyGridConfigToAllHandler $applyToAll,
    ): JsonResponse {
        $applyToAll($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/grid-mark-favourite-column-config', name: 'gridmarkfavouritecolumnconfig', methods: ['POST'])]
    public function gridMarkFavouriteColumnConfigAction(
        MarkDataObjectGridConfigFavouritePayload $payload,
        MarkDataObjectGridConfigFavouriteHandler $markFavourite,
    ): JsonResponse {
        $result = $markFavourite($payload);

        return $this->adminJson(ApiResponse::ok(['specializedConfigs' => $result->specializedConfigs]));
    }

    #[Route('/grid-save-column-config', name: 'gridsavecolumnconfig', methods: ['POST'])]
    public function gridSaveColumnConfigAction(
        SaveDataObjectGridColumnConfigPayload $payload,
        SaveDataObjectGridColumnConfigHandler $saveGridColumnConfig,
    ): JsonResponse {
        $result = $saveGridColumnConfig($payload);

        return $this->adminJson(ApiResponse::ok([
            'settings'         => $result->settings,
            'availableConfigs' => $result->availableConfigs,
            'sharedConfigs'    => $result->sharedConfigs,
        ]));
    }

    /**
     * IMPORTER
     */
    #[Route('/import-upload', name: 'importupload', methods: ['POST'])]
    public function importUploadAction(
        ImportUploadPayload $payload,
        ImportUploadHandler $importUpload,
    ): JsonResponse {
        $importUpload($payload);

        $response = $this->adminJson(ApiResponse::ok());

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/get-export-jobs', name: 'getexportjobs', methods: ['POST'])]
    public function getExportJobsAction(
        GetExportJobsPayload $payload,
        GetExportJobsHandler $handler,
        Request $request,
    ): JsonResponse {
        if ($payload->requestedLanguage !== $request->getLocale()) {
            $request->setLocale($payload->requestedLanguage);
        }

        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['jobs' => $result->jobs, 'fileHandle' => $result->fileHandle]));
    }

    #[Route('/do-export', name: 'doexport', methods: ['POST'])]
    public function doExportAction(
        DoDataObjectExportPayload $payload,
        DoDataObjectExportHandler $doExport,
        Request $request,
    ): JsonResponse {
        if ($payload->requestedLanguage !== $request->getLocale()) {
            $request->setLocale($payload->requestedLanguage);
        }

        $doExport($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/download-csv-file', name: 'downloadcsvfile', methods: ['GET'])]
    public function downloadCsvFileAction(
        #[MapQueryParameter] ?string $fileHandle = null,
    ): Response {
        try {
            return $this->gridExportService->downloadCsvFile($fileHandle);
        } catch (\RuntimeException) {
            throw $this->createNotFoundException('CSV file not found');
        }
    }

    #[Route('/download-xlsx-file', name: 'downloadxlsxfile', methods: ['GET'])]
    public function downloadXlsxFileAction(
        #[MapQueryParameter] ?string $fileHandle = null,
    ): BinaryFileResponse {
        try {
            return $this->gridExportService->downloadXlsxFile($fileHandle);
        } catch (\RuntimeException) {
            throw $this->createNotFoundException('XLSX file not found');
        }
    }

    #[Route('/get-batch-jobs', name: 'getbatchjobs', methods: ['POST'])]
    public function getBatchJobsAction(
        GetBatchJobsPayload $payload,
        GetBatchJobsHandler $handler,
        Request $request,
    ): JsonResponse {
        if ($payload->locale !== $request->getLocale()) {
            $request->setLocale($payload->locale);
        }

        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['jobs' => $result->jobs]));
    }

    #[Route('/batch', name: 'batch', methods: ['PUT'])]
    public function batchAction(
        ExecuteBatchPayload $payload,
        ExecuteBatchHandler $handler,
    ): JsonResponse {
        if (!$payload->hasData) {
            return $this->adminJson(ApiResponse::ok());
        }

        $saved = $handler($payload);

        return $this->adminJson(ApiResponse::fromBool($saved));
    }

    #[Route('/get-available-visible-vields', name: 'getavailablevisiblefields', methods: ['GET'])]
    public function getAvailableVisibleFieldsAction(
        GetAvailableVisibleFieldsPayload $payload,
        GetAvailableVisibleFieldsHandler $getAvailableFields,
    ): JsonResponse {
        $result = $getAvailableFields($payload);

        return $this->adminJson(['availableFields' => $result->availableFields]);
    }
}
