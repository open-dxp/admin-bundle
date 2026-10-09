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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ApplyGridConfigToAll\ApplyGridConfigToAllHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ApplyGridConfigToAll\ApplyGridConfigToAllPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DeleteGridColumnConfig\DeleteGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DeleteGridColumnConfig\DeleteGridColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ExecuteBatch\ExecuteBatchHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ExecuteBatch\ExecuteBatchPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetAvailableVisibleFields\GetAvailableVisibleFieldsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetAvailableVisibleFields\GetAvailableVisibleFieldsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetBatchJobs\GetBatchJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetBatchJobs\GetBatchJobsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetExportConfigs\GetExportConfigsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetExportConfigs\GetExportConfigsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetGridColumnConfig\GetGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetGridColumnConfig\GetGridColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ImportUpload\ImportUploadHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ImportUpload\ImportUploadPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\LoadObjectData\LoadObjectDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\LoadObjectData\LoadObjectDataPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\MarkDataObjectGridConfigFavourite\MarkDataObjectGridConfigFavouriteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\MarkDataObjectGridConfigFavourite\MarkDataObjectGridConfigFavouritePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\PrepareHelperColumnConfigs\PrepareHelperColumnConfigsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\PrepareHelperColumnConfigs\PrepareHelperColumnConfigsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\SaveDataObjectGridColumnConfig\SaveDataObjectGridColumnConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\SaveDataObjectGridColumnConfig\SaveDataObjectGridColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\GridColumnConfigSessionGateway;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/object-helper', name: 'opendxp_admin_dataobject_dataobjecthelper_')]
class DataObjectHelperController extends AdminAbstractController
{
    #[Route('/load-object-data', name: 'loadobjectdata', methods: ['GET'])]
    #[SessionGatewayAware(GridColumnConfigSessionGateway::class)]
    public function loadObjectDataAction(
        LoadObjectDataPayload $payload,
        LoadObjectDataHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/get-export-configs', name: 'getexportconfigs', methods: ['GET'])]
    public function getExportConfigsAction(
        GetExportConfigsPayload $payload,
        GetExportConfigsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/grid-delete-column-config', name: 'griddeletecolumnconfig', methods: ['DELETE'])]
    #[SessionGatewayAware(GridColumnConfigSessionGateway::class)]
    public function gridDeleteColumnConfigAction(
        DeleteGridColumnConfigPayload $payload,
        DeleteGridColumnConfigHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[Route('/grid-get-column-config', name: 'gridgetcolumnconfig', methods: ['GET'])]
    #[SessionGatewayAware(GridColumnConfigSessionGateway::class)]
    public function gridGetColumnConfigAction(
        GetGridColumnConfigPayload $payload,
        GetGridColumnConfigHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[Route('/prepare-helper-column-configs', name: 'preparehelpercolumnconfigs', methods: ['POST'])]
    #[SessionGatewayAware(GridColumnConfigSessionGateway::class)]
    public function prepareHelperColumnConfigs(
        PrepareHelperColumnConfigsPayload $payload,
        PrepareHelperColumnConfigsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/grid-config-apply-to-all', name: 'gridconfigapplytoall', methods: ['POST'])]
    public function gridConfigApplyToAllAction(
        ApplyGridConfigToAllPayload $payload,
        ApplyGridConfigToAllHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/grid-mark-favourite-column-config', name: 'gridmarkfavouritecolumnconfig', methods: ['POST'])]
    public function gridMarkFavouriteColumnConfigAction(
        MarkDataObjectGridConfigFavouritePayload $payload,
        MarkDataObjectGridConfigFavouriteHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/grid-save-column-config', name: 'gridsavecolumnconfig', methods: ['POST'])]
    public function gridSaveColumnConfigAction(
        SaveDataObjectGridColumnConfigPayload $payload,
        SaveDataObjectGridColumnConfigHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    /**
     * IMPORTER
     */
    #[AsHtmlContentTypeResponse]
    #[Route('/import-upload', name: 'importupload', methods: ['POST'])]
    public function importUploadAction(
        ImportUploadPayload $payload,
        ImportUploadHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
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

        return $this->apiJson($handler($payload));
    }

    #[Route('/batch', name: 'batch', methods: ['PUT'])]
    public function batchAction(
        ExecuteBatchPayload $payload,
        ExecuteBatchHandler $handler,
    ): JsonResponse {
        if ($payload->hasData) {
            $handler($payload);
        }

        return $this->apiOk();
    }

    #[Route('/get-available-visible-vields', name: 'getavailablevisiblefields', methods: ['GET'])]
    public function getAvailableVisibleFieldsAction(
        GetAvailableVisibleFieldsPayload $payload,
        GetAvailableVisibleFieldsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), envelope: false);
    }
}
