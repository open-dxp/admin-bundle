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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ExecuteBatchHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetBatchJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetExportConfigsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetExportJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DeleteDataObjectGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DeleteGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DoDataObjectExportHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ImportUploadHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\LoadObjectDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetAvailableVisibleFieldsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\MarkDataObjectGridConfigFavouriteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\PrepareHelperColumnConfigsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\SaveDataObjectGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\File;
use OpenDxp\Tool;
use stdClass;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

/**
 * @internal
 */
#[Route('/object-helper', name: 'opendxp_admin_dataobject_dataobjecthelper_')]
class DataObjectHelperController extends AdminAbstractController
{
    public function __construct(private readonly GridExportService $gridExportService) {}

    #[Route('/load-object-data', name: 'loadobjectdata', methods: ['GET'])]
    public function loadObjectDataAction(
        LoadObjectDataHandler $handler,
        Request $request,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['fields' => $handler($id, $request->query->all('fields'))]));
    }

    #[Route('/get-export-configs', name: 'getexportconfigs', methods: ['GET'])]
    public function getExportConfigsAction(
        GetExportConfigsHandler $getExportConfigs,
        #[MapQueryParameter] ?string $classId = null,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $getExportConfigs($classId)]));
    }

    #[Route('/grid-delete-column-config', name: 'griddeletecolumnconfig', methods: ['DELETE'])]
    public function gridDeleteColumnConfigAction(
        DeleteDataObjectGridColumnConfigHandler $deleteGridColumnConfig,
        DeleteGridColumnConfigHandler $handler,
        Request $request,
        #[MapQueryParameter(name: 'no_system_columns')] bool $noSystemColumns = false,
        #[MapQueryParameter(name: 'no_brick_columns')] bool $noBrickColumns = false,
    ): JsonResponse {
        $params = [
            'id'              => $request->request->get('id'),
            'objectId'        => $request->request->get('objectId'),
            'name'            => $request->request->get('name'),
            'type'            => $request->request->get('type'),
            'types'           => $request->request->get('types'),
            'gridtype'        => $request->request->get('gridtype'),
            'gridConfigId'    => $request->request->get('gridConfigId'),
            'searchType'      => $request->request->get('searchType'),
            'noSystemColumns' => $noSystemColumns,
            'noBrickColumns'  => $noBrickColumns,
            'locale'          => $request->getLocale(),
        ];

        $deleteGridColumnConfig((int) $request->request->get('gridConfigId'));

        return $this->adminJson($handler($request, $params));
    }

    #[Route('/grid-get-column-config', name: 'gridgetcolumnconfig', methods: ['GET'])]
    public function gridGetColumnConfigAction(
        GetGridColumnConfigHandler $handler,
        Request $request,
        #[MapQueryParameter] ?string $id = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $objectId = null,
        #[MapQueryParameter] ?string $name = null,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] ?string $types = null,
        #[MapQueryParameter] ?string $gridtype = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $gridConfigId = null,
        #[MapQueryParameter] ?string $searchType = null,
        #[MapQueryParameter(name: 'no_system_columns')] bool $noSystemColumns = false,
        #[MapQueryParameter(name: 'no_brick_columns')] bool $noBrickColumns = false,
    ): JsonResponse {
        $params = [
            'id'              => $id,
            'objectId'        => $objectId,
            'name'            => $name,
            'type'            => $type,
            'types'           => $types,
            'gridtype'        => $gridtype,
            'gridConfigId'    => $gridConfigId,
            'searchType'      => $searchType,
            'noSystemColumns' => $noSystemColumns,
            'noBrickColumns'  => $noBrickColumns,
        ];

        return $this->adminJson($handler($request, $params));
    }

    #[Route('/prepare-helper-column-configs', name: 'preparehelpercolumnconfigs', methods: ['POST'])]
    public function prepareHelperColumnConfigs(Request $request, PrepareHelperColumnConfigsHandler $prepareHelperColumns): JsonResponse
    {
        /** @var stdClass[] $columns */
        $columns = json_decode($request->request->get('columns'));

        $existingHelperColumns = Tool\Session::useBag(
            $request->getSession(),
            static fn(AttributeBagInterface $bag) => $bag->get('helpercolumns', []),
            'opendxp_gridconfig',
        );

        $result = $prepareHelperColumns($columns, $existingHelperColumns);

        Tool\Session::useBag(
            $request->getSession(),
            static function (AttributeBagInterface $bag) use ($result): void {
                $bag->set('helpercolumns', $result['helperColumns']);
            },
            'opendxp_gridconfig',
        );

        return $this->adminJson(ApiResponse::ok(['columns' => $result['newData']]));
    }

    #[Route('/grid-config-apply-to-all', name: 'gridconfigapplytoall', methods: ['POST'])]
    public function gridConfigApplyToAllAction(ApplyGridConfigToAllHandler $applyToAll, Request $request): JsonResponse
    {
        $applyToAll(
            $request->request->getInt('objectId'),
            (string) $request->request->get('classId'),
            (string) $request->request->get('searchType'),
            );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/grid-mark-favourite-column-config', name: 'gridmarkfavouritecolumnconfig', methods: ['POST'])]
    public function gridMarkFavouriteColumnConfigAction(
        MarkDataObjectGridConfigFavouriteHandler $markFavourite,
        Request $request,
    ): JsonResponse {
        $result = $markFavourite(
            $request->request->getInt('objectId'),
            $request->request->get('classId'),
            (int) $request->request->get('gridConfigId'),
            $request->request->get('searchType'),
            (bool) $request->request->get('global'),
            $request->request->get('type'),
            );

        return $this->adminJson(ApiResponse::ok(['specializedConfigs' => $result->specializedConfigs]));
    }

    #[Route('/grid-save-column-config', name: 'gridsavecolumnconfig', methods: ['POST'])]
    public function gridSaveColumnConfigAction(
        SaveDataObjectGridColumnConfigHandler $saveGridColumnConfig,
        Request $request,
    ): JsonResponse {
        $gridConfigData = $this->decodeJson($request->request->get('gridconfig'));
        $metadata = json_decode($request->request->get('settings'), true);

        $result = $saveGridColumnConfig(
            $request->request->getInt('id'),
            $request->request->get('class_id'),
            $request->request->get('context'),
            $request->request->get('searchType'),
            $gridConfigData,
            $metadata,
            );

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
    public function importUploadAction(Request $request, ImportUploadHandler $importUpload): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('Filedata');

        $importUpload(file_get_contents($file->getPathname()), (string) $request->request->get('importId'));

        $response = $this->adminJson(ApiResponse::ok());

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/get-export-jobs', name: 'getexportjobs', methods: ['POST'])]
    public function getExportJobsAction(Request $request, GetExportJobsHandler $handler): JsonResponse
    {
        $requestedLanguage = $this->extractLanguage($request);
        $allParams = [...$request->request->all(), ...$request->query->all()];

        $result = $handler($allParams, $requestedLanguage);

        return $this->adminJson(ApiResponse::ok(['jobs' => $result->jobs, 'fileHandle' => $result->fileHandle]));
    }

    #[Route('/do-export', name: 'doexport', methods: ['POST'])]
    public function doExportAction(DoDataObjectExportHandler $doExport, Request $request): JsonResponse
    {
        $fileHandle = File::getValidFilename($request->request->get('fileHandle'));
        $settings = json_decode($request->request->get('settings'), true);
        $fields = json_decode($request->request->all('fields')[0], true);

        $allParams = [...$request->request->all(), ...$request->query->all()];

        $context = ['source' => 'opendxp-export'];
        $contextFromRequest = $request->request->get('context');
        if ($contextFromRequest) {
            $context = [...$context, ...json_decode($contextFromRequest, true)];
        }

        $doExport(
            $fileHandle,
            $request->request->all('ids'),
            (string) $request->request->get('classId'),
            $settings['delimiter'] ?? ';',
            $settings['header'] ?? 'title',
            $request->request->get('userTimezone'),
            $allParams,
            $this->extractLanguage($request),
            $fields,
            (bool) $request->request->get('initial'),
            (bool) ($settings['enableInheritance'] ?? false),
            $context,
        );

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
    public function getBatchJobsAction(Request $request, GetBatchJobsHandler $handler): JsonResponse
    {
        if ($request->request->get('language')) {
            $request->setLocale($request->request->get('language'));
        }

        $allParams = [...$request->request->all(), ...$request->query->all()];
        $result = $handler($allParams, $request->getLocale());

        return $this->adminJson(ApiResponse::ok(['jobs' => $result->jobs]));
    }

    #[Route('/batch', name: 'batch', methods: ['PUT'])]
    public function batchAction(Request $request, ExecuteBatchHandler $handler): JsonResponse
    {
        if ($request->request->has('data')) {
            $params = $this->decodeJson($request->request->get('data'), true);
            $saved = $handler($params, $request->getLocale());

            return $this->adminJson(ApiResponse::fromBool($saved));
        }

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/get-available-visible-vields', name: 'getavailablevisiblefields', methods: ['GET'])]
    public function getAvailableVisibleFieldsAction(
        GetAvailableVisibleFieldsHandler $getAvailableFields,
        #[MapQueryParameter] ?string $classes = null,
    ): JsonResponse {
        $result = $getAvailableFields($classes);

        return $this->adminJson(['availableFields' => $result->availableFields]);
    }

    private function extractLanguage(Request $request): string
    {
        $requestedLanguage = $request->request->get('language');
        if ($requestedLanguage) {
            if ($requestedLanguage !== 'default') {
                $request->setLocale($requestedLanguage);
            }
        } else {
            $requestedLanguage = $request->getLocale();
        }

        return $requestedLanguage;
    }
}
