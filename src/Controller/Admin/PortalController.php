<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\AddWidgetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\CreateDashboardHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\DeleteDashboardHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetDashboardConfigurationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetDashboardListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetModificationStatisticsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetModifiedAssetsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetModifiedDocumentsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetModifiedObjectsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\RemoveWidgetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\ReorderWidgetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\UpdatePortletConfigHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/portal')]
class PortalController extends AdminAbstractController
{
    #[Route('/dashboard-list', name: 'opendxp_admin_portal_dashboardlist', methods: ['GET'])]
    public function dashboardListAction(GetDashboardListHandler $getDashboardList): JsonResponse
    {
        $result = $getDashboardList();

        return $this->adminJson($result->dashboards);
    }

    #[Route('/create-dashboard', name: 'opendxp_admin_portal_createdashboard', methods: ['POST'])]
    public function createDashboardAction(Request $request, CreateDashboardHandler $createDashboard): JsonResponse
    {
        $key = trim($request->request->get('key', ''));

        try {
            $createDashboard($key);
        } catch (\InvalidArgumentException $e) {
            return $this->adminJson(ApiResponse::error($e->getMessage()));
        }

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/delete-dashboard', name: 'opendxp_admin_portal_deletedashboard', methods: ['DELETE'])]
    public function deleteDashboardAction(Request $request, DeleteDashboardHandler $deleteDashboard): JsonResponse
    {
        $deleteDashboard((string) $request->request->get('key'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/get-configuration', name: 'opendxp_admin_portal_getconfiguration', methods: ['GET'])]
    public function getConfigurationAction(
        GetDashboardConfigurationHandler $getDashboardConfiguration,
        #[MapQueryParameter] ?string $key = null,
    ): JsonResponse {
        $result = $getDashboardConfiguration($key);

        return $this->adminJson($result->config);
    }

    #[Route('/remove-widget', name: 'opendxp_admin_portal_removewidget', methods: ['DELETE'])]
    public function removeWidgetAction(Request $request, RemoveWidgetHandler $removeWidget): JsonResponse
    {
        $widgetId = $request->request->has('id') ? (int) $request->request->get('id') : null;
        $removeWidget(
            dashboardId: (string) $request->request->get('key'),
            widgetId: $widgetId,
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/add-widget', name: 'opendxp_admin_portal_addwidget', methods: ['POST'])]
    public function addWidgetAction(Request $request, AddWidgetHandler $addWidget): JsonResponse
    {
        $result = $addWidget(
            dashboardId: (string) $request->request->get('key'),
            type: (string) $request->request->get('type'),
        );

        return $this->adminJson(ApiResponse::ok(['id' => $result->id]));
    }

    #[Route('/reorder-widget', name: 'opendxp_admin_portal_reorderwidget', methods: ['PUT'])]
    public function reorderWidgetAction(Request $request, ReorderWidgetHandler $reorderWidget): JsonResponse
    {
        $widgetId = $request->request->has('id') ? (int) $request->request->get('id') : null;
        $reorderWidget(
            dashboardId: (string) $request->request->get('key'),
            widgetId: $widgetId,
            column: $request->request->getInt('column'),
            row: $request->request->getInt('row'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/update-portlet-config', name: 'opendxp_admin_portal_updateportletconfig', methods: ['PUT'])]
    public function updatePortletConfigAction(Request $request, UpdatePortletConfigHandler $updatePortletConfig): JsonResponse
    {
        $portletId = $request->request->has('id') ? (int) $request->request->get('id') : null;
        $updatePortletConfig(
            dashboardKey: (string) $request->request->get('key'),
            portletId: $portletId,
            configuration: $request->request->get('config'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/portlet-modified-documents', name: 'opendxp_admin_portal_portletmodifieddocuments', methods: ['GET'])]
    public function portletModifiedDocumentsAction(GetModifiedDocumentsHandler $getModifiedDocuments): JsonResponse
    {
        $result = $getModifiedDocuments();

        return $this->adminJson(['documents' => $result->documents]);
    }

    #[Route('/portlet-modified-assets', name: 'opendxp_admin_portal_portletmodifiedassets', methods: ['GET'])]
    public function portletModifiedAssetsAction(GetModifiedAssetsHandler $getModifiedAssets): JsonResponse
    {
        $result = $getModifiedAssets();

        return $this->adminJson(['assets' => $result->assets]);
    }

    #[Route('/portlet-modified-objects', name: 'opendxp_admin_portal_portletmodifiedobjects', methods: ['GET'])]
    public function portletModifiedObjectsAction(GetModifiedObjectsHandler $getModifiedObjects): JsonResponse
    {
        $result = $getModifiedObjects();

        return $this->adminJson(['objects' => $result->objects]);
    }

    #[Route('/portlet-modification-statistics', name: 'opendxp_admin_portal_portletmodificationstatistics', methods: ['GET'])]
    public function portletModificationStatisticsAction(GetModificationStatisticsHandler $getModificationStatistics): JsonResponse
    {
        $result = $getModificationStatistics();

        return $this->adminJson(['data' => $result->data]);
    }
}
