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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Asset;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\DeleteGridColumnConfig\DeleteGridColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\DeleteGridColumnConfig\DeleteGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\DoAssetExport\DoAssetExportPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\DoAssetExport\DoAssetExportHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\ExecuteAssetBatch\ExecuteAssetBatchPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\ExecuteAssetBatch\ExecuteAssetBatchHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetAssetBatchJobs\GetAssetBatchJobsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetAssetBatchJobs\GetAssetBatchJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\PrepareHelperColumnConfigs\PrepareHelperColumnConfigsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\PrepareHelperColumnConfigs\PrepareHelperColumnConfigsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetAssetMetadataForColumnConfig\GetAssetMetadataForColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetExportJobs\GetExportJobsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetExportJobs\GetExportJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetGridColumnConfig\GetGridColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetGridColumnConfig\GetGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\MarkGridConfigFavourite\MarkGridConfigFavouritePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\MarkGridConfigFavourite\MarkGridConfigFavouriteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\SaveGridColumnConfig\SaveGridColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\SaveGridColumnConfig\SaveGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/asset-helper')]
class AssetHelperController extends AdminAbstractController
{
    #[Route('/grid-delete-column-config', name: 'opendxp_admin_asset_assethelper_griddeletecolumnconfig', methods: ['DELETE'])]
    public function gridDeleteColumnConfigAction(
        DeleteGridColumnConfigPayload $payload,
        DeleteGridColumnConfigHandler $handler,
    ): JsonResponse {
        return $this->adminJson($handler($payload));
    }

    #[Route('/grid-get-column-config', name: 'opendxp_admin_asset_assethelper_gridgetcolumnconfig', methods: ['GET'])]
    public function gridGetColumnConfigAction(
        GetGridColumnConfigPayload $payload,
        GetGridColumnConfigHandler $handler,
    ): JsonResponse {
        return $this->adminJson($handler($payload));
    }

    #[Route('/prepare-helper-column-configs', name: 'opendxp_admin_asset_assethelper_preparehelpercolumnconfigs', methods: ['POST'])]
    public function prepareHelperColumnConfigs(
        PrepareHelperColumnConfigsPayload $payload,
        PrepareHelperColumnConfigsHandler $handler,
        Request $request,
    ): JsonResponse {
        $result = $handler($payload);

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($result): void {
            $existingColumns = $session->get('helpercolumns', []);
            $helperColumns = [...$result->helperColumns, ...$existingColumns];
            $session->set('helpercolumns', $helperColumns);
        }, 'opendxp_gridconfig');

        return $this->adminJson(ApiResponse::ok(['columns' => $result->newData]));
    }

    #[Route('/grid-mark-favourite-column-config', name: 'opendxp_admin_asset_assethelper_gridmarkfavouritecolumnconfig', methods: ['POST'])]
    public function gridMarkFavouriteColumnConfigAction(
        MarkGridConfigFavouritePayload $payload,
        MarkGridConfigFavouriteHandler $handler,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['specializedConfigs' => $result->specializedConfigs]));
    }

    #[Route('/grid-save-column-config', name: 'opendxp_admin_asset_assethelper_gridsavecolumnconfig', methods: ['POST'])]
    public function gridSaveColumnConfigAction(
        SaveGridColumnConfigPayload $payload,
        SaveGridColumnConfigHandler $handler,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok([
            'settings'         => $result->settings,
            'availableConfigs' => $result->availableConfigs,
            'sharedConfigs'    => $result->sharedConfigs,
        ]));
    }

    #[Route('/get-export-jobs', name: 'opendxp_admin_asset_assethelper_getexportjobs', methods: ['POST'])]
    public function getExportJobsAction(GetExportJobsPayload $payload, GetExportJobsHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['jobs' => $result->jobs, 'fileHandle' => $result->fileHandle]));
    }

    #[Route('/do-export', name: 'opendxp_admin_asset_assethelper_doexport', methods: ['POST'])]
    public function doExportAction(DoAssetExportPayload $payload, DoAssetExportHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/download-csv-file', name: 'opendxp_admin_asset_assethelper_downloadcsvfile', methods: ['GET'])]
    public function downloadCsvFileAction(
        GridExportService $gridExportService,
        #[MapQueryParameter] ?string $fileHandle = null,
    ): Response {
        try {
            return $gridExportService->downloadCsvFile($fileHandle ?? '');
        } catch (\RuntimeException) {
            throw $this->createNotFoundException('CSV file not found');
        }
    }

    #[Route('/download-xlsx-file', name: 'opendxp_admin_asset_assethelper_downloadxlsxfile', methods: ['GET'])]
    public function downloadXlsxFileAction(
        GridExportService $gridExportService,
        #[MapQueryParameter] ?string $fileHandle = null,
    ): BinaryFileResponse {
        try {
            return $gridExportService->downloadXlsxFile($fileHandle ?? '');
        } catch (\RuntimeException) {
            throw $this->createNotFoundException('XLSX file not found');
        }
    }

    #[Route('/get-metadata-for-column-config', name: 'opendxp_admin_asset_assethelper_getmetadataforcolumnconfig', methods: ['GET'])]
    public function getMetadataForColumnConfigAction(GetAssetMetadataForColumnConfigHandler $handler): JsonResponse
    {
        $result = $handler();

        return $this->adminJson($result->data);
    }

    #[Route('/get-batch-jobs', name: 'opendxp_admin_asset_assethelper_getbatchjobs', methods: ['POST'])]
    public function getBatchJobsAction(GetAssetBatchJobsPayload $payload, GetAssetBatchJobsHandler $handler, Request $request): JsonResponse
    {
        if ($payload->language) {
            $request->setLocale($payload->language);
        }

        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['jobs' => $result->jobs]));
    }

    #[Route('/batch', name: 'opendxp_admin_asset_assethelper_batch', methods: ['PUT'])]
    public function batchAction(ExecuteAssetBatchPayload $payload, ExecuteAssetBatchHandler $handler): JsonResponse
    {
        if ($payload->data !== null && !$handler($payload)) {
            return $this->adminJson(ApiResponse::error('AssetHelperController::batchAction => There is no asset left to update.'));
        }

        return $this->adminJson(ApiResponse::ok());
    }
}
