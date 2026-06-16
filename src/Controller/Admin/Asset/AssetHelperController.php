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
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\DeleteGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\DoAssetExportHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\ExecuteAssetBatchHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetAssetBatchJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetAssetMetadataForColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetExportJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\MarkGridConfigFavouriteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\SaveGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Service\Grid\AssetGridColumnConfigResolver;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\File;
use OpenDxp\Tool\Session;
use stdClass;
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
    public function __construct(
        private readonly AssetGridColumnConfigResolver $gridConfigResolver,
    ) {
    }

    #[Route('/grid-delete-column-config', name: 'opendxp_admin_asset_assethelper_griddeletecolumnconfig', methods: ['DELETE'])]
    public function gridDeleteColumnConfigAction(
        DeleteGridColumnConfigHandler $deleteGridColumnConfig,
        Request $request,
        #[MapQueryParameter(name: 'no_system_columns')] bool $noSystemColumns = false,
    ): JsonResponse {
        $params = [
            'id'              => $request->request->get('id'),
            'type'            => $request->request->get('type'),
            'types'           => $request->request->get('types'),
            'gridConfigId'    => $request->request->get('gridConfigId'),
            'searchType'      => $request->request->get('searchType'),
            'noSystemColumns' => $noSystemColumns,
        ];

        $deleteGridColumnConfig(
            (int) $request->request->get('gridConfigId'),
            );

        $resolverResult = $this->gridConfigResolver->resolve($params, true);

        return $this->adminJson([...$resolverResult->jsonSerialize(), 'deleteSuccess' => true]);
    }

    #[Route('/grid-get-column-config', name: 'opendxp_admin_asset_assethelper_gridgetcolumnconfig', methods: ['GET'])]
    public function gridGetColumnConfigAction(
        #[MapQueryParameter] ?string $id = null,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] ?string $types = null,
        #[MapQueryParameter] ?string $gridConfigId = null,
        #[MapQueryParameter] ?string $searchType = null,
        #[MapQueryParameter(name: 'no_system_columns')] bool $noSystemColumns = false,
    ): JsonResponse {
        $params = [
            'id'              => $id,
            'type'            => $type,
            'types'           => $types,
            'gridConfigId'    => $gridConfigId,
            'searchType'      => $searchType,
            'noSystemColumns' => $noSystemColumns,
        ];

        return $this->adminJson($this->gridConfigResolver->resolve($params));
    }

    #[Route('/prepare-helper-column-configs', name: 'opendxp_admin_asset_assethelper_preparehelpercolumnconfigs', methods: ['POST'])]
    public function prepareHelperColumnConfigs(Request $request): JsonResponse
    {
        $helperColumns = [];
        $newData = [];
        $data = json_decode($request->request->get('columns'));

        /** @var stdClass $item */
        foreach ($data as $item) {
            if (!empty($item->isOperator)) {
                $itemKey = '#' . uniqid('', false);

                $item->key = $itemKey;
                $newData[] = $item;
                $helperColumns[$itemKey] = $item;
            } else {
                $newData[] = $item;
            }
        }

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($helperColumns): void {
            $existingColumns = $session->get('helpercolumns', []);
            $helperColumns = [...$helperColumns, ...$existingColumns];
            $session->set('helpercolumns', $helperColumns);
        }, 'opendxp_gridconfig');

        return $this->adminJson(ApiResponse::ok(['columns' => $newData]));
    }

    #[Route('/grid-mark-favourite-column-config', name: 'opendxp_admin_asset_assethelper_gridmarkfavouritecolumnconfig', methods: ['POST'])]
    public function gridMarkFavouriteColumnConfigAction(
        MarkGridConfigFavouriteHandler $markFavourite,
        Request $request,
    ): JsonResponse {
        $result = $markFavourite(
            $request->request->get('classId'),
            (int) $request->request->get('gridConfigId'),
            $request->request->get('searchType'),
            $request->request->get('type'),
            );

        return $this->adminJson(ApiResponse::ok(['specializedConfigs' => $result->specializedConfigs]));
    }

    #[Route('/grid-save-column-config', name: 'opendxp_admin_asset_assethelper_gridsavecolumnconfig', methods: ['POST'])]
    public function gridSaveColumnConfigAction(
        SaveGridColumnConfigHandler $saveGridColumnConfig,
        Request $request,
    ): JsonResponse {
        $gridConfigData = $this->decodeJson($request->request->get('gridconfig'));
        $metadata = json_decode($request->request->get('settings'), true);

        $result = $saveGridColumnConfig(
            (int) $request->request->get('id'),
            $request->request->get('class_id'),
            $request->request->get('context'),
            $request->request->get('searchType'),
            $request->request->get('type'),
            $gridConfigData,
            $metadata,
            );

        return $this->adminJson(ApiResponse::ok([
            'settings'         => $result->settings,
            'availableConfigs' => $result->availableConfigs,
            'sharedConfigs'    => $result->sharedConfigs,
        ]));
    }

    #[Route('/get-export-jobs', name: 'opendxp_admin_asset_assethelper_getexportjobs', methods: ['POST'])]
    public function getExportJobsAction(GetExportJobsHandler $getExportJobs, Request $request): JsonResponse
    {
        $allParams = [...$request->request->all(), ...$request->query->all()];
        $result = $getExportJobs($allParams);

        return $this->adminJson(ApiResponse::ok(['jobs' => $result->jobs, 'fileHandle' => $result->fileHandle]));
    }

    #[Route('/do-export', name: 'opendxp_admin_asset_assethelper_doexport', methods: ['POST'])]
    public function doExportAction(DoAssetExportHandler $doExport, Request $request): JsonResponse
    {
        $fileHandle = File::getValidFilename($request->request->get('fileHandle'));
        $settings = json_decode($request->request->get('settings'), true);
        $fields = json_decode($request->request->all('fields')[0], true);

        $doExport(
            $fileHandle,
            $request->request->all('ids'),
            $settings['delimiter'] ?? ';',
            str_replace('default', '', $request->request->get('language')),
            $settings['header'] ?? 'title',
            $fields,
            (bool) $request->request->get('initial'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/download-csv-file', name: 'opendxp_admin_asset_assethelper_downloadcsvfile', methods: ['GET'])]
    public function downloadCsvFileAction(
        GridExportService $gridExportService,
        #[MapQueryParameter] ?string $fileHandle = null,
    ): Response {
        try {
            return $gridExportService->downloadCsvFile($fileHandle);
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
            return $gridExportService->downloadXlsxFile($fileHandle);
        } catch (\RuntimeException) {
            throw $this->createNotFoundException('XLSX file not found');
        }
    }

    #[Route('/get-metadata-for-column-config', name: 'opendxp_admin_asset_assethelper_getmetadataforcolumnconfig', methods: ['GET'])]
    public function getMetadataForColumnConfigAction(GetAssetMetadataForColumnConfigHandler $getMetadata): JsonResponse
    {
        $result = $getMetadata();

        return $this->adminJson($result->data);
    }

    #[Route('/get-batch-jobs', name: 'opendxp_admin_asset_assethelper_getbatchjobs', methods: ['POST'])]
    public function getBatchJobsAction(GetAssetBatchJobsHandler $getAssetBatchJobs, Request $request): JsonResponse
    {
        if ($request->request->get('language')) {
            $request->setLocale($request->request->get('language'));
        }

        $allParams = [...$request->request->all(), ...$request->query->all()];
        $result = $getAssetBatchJobs($allParams);

        return $this->adminJson(ApiResponse::ok(['jobs' => $result->jobs]));
    }

    #[Route('/batch', name: 'opendxp_admin_asset_assethelper_batch', methods: ['PUT'])]
    public function batchAction(ExecuteAssetBatchHandler $executeAssetBatch, Request $request): JsonResponse
    {
        if ($request->request->has('data')) {
            $data = $this->decodeJson($request->request->get('data'), true);
            $executeAssetBatch($data);
        }

        return $this->adminJson(ApiResponse::ok());
    }
}
