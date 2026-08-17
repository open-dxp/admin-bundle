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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\AddWidget\AddWidgetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\AddWidget\AddWidgetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\CreateDashboard\CreateDashboardHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\CreateDashboard\CreateDashboardPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\DeleteDashboard\DeleteDashboardHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\DeleteDashboard\DeleteDashboardPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetDashboardConfiguration\GetDashboardConfigurationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetDashboardConfiguration\GetDashboardConfigurationPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetDashboardList\GetDashboardListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetModificationStatistics\GetModificationStatisticsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetModifiedAssets\GetModifiedAssetsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetModifiedDocuments\GetModifiedDocumentsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\GetModifiedObjects\GetModifiedObjectsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\RemoveWidget\RemoveWidgetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\RemoveWidget\RemoveWidgetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\ReorderWidget\ReorderWidgetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\ReorderWidget\ReorderWidgetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\UpdatePortletConfig\UpdatePortletConfigHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Portal\UpdatePortletConfig\UpdatePortletConfigPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/portal')]
class PortalController extends AdminAbstractController
{
    #[Route('/dashboard-list', name: 'opendxp_admin_portal_dashboardlist', methods: ['GET'])]
    public function dashboardListAction(
        GetDashboardListHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler(), rootProperty: 'dashboards');
    }

    #[Route('/create-dashboard', name: 'opendxp_admin_portal_createdashboard', methods: ['POST'])]
    public function createDashboardAction(
        CreateDashboardHandler $handler,
        CreateDashboardPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/delete-dashboard', name: 'opendxp_admin_portal_deletedashboard', methods: ['DELETE'])]
    public function deleteDashboardAction(
        DeleteDashboardHandler $handler,
        DeleteDashboardPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/get-configuration', name: 'opendxp_admin_portal_getconfiguration', methods: ['GET'])]
    public function getConfigurationAction(
        GetDashboardConfigurationHandler $handler,
        GetDashboardConfigurationPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'config');
    }

    #[Route('/remove-widget', name: 'opendxp_admin_portal_removewidget', methods: ['DELETE'])]
    public function removeWidgetAction(
        RemoveWidgetHandler $handler,
        RemoveWidgetPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/add-widget', name: 'opendxp_admin_portal_addwidget', methods: ['POST'])]
    public function addWidgetAction(
        AddWidgetHandler $handler,
        AddWidgetPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/reorder-widget', name: 'opendxp_admin_portal_reorderwidget', methods: ['PUT'])]
    public function reorderWidgetAction(
        ReorderWidgetHandler $handler,
        ReorderWidgetPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/update-portlet-config', name: 'opendxp_admin_portal_updateportletconfig', methods: ['PUT'])]
    public function updatePortletConfigAction(
        UpdatePortletConfigHandler $handler,
        UpdatePortletConfigPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/portlet-modified-documents', name: 'opendxp_admin_portal_portletmodifieddocuments', methods: ['GET'])]
    public function portletModifiedDocumentsAction(
        GetModifiedDocumentsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler(), envelope: false);
    }

    #[Route('/portlet-modified-assets', name: 'opendxp_admin_portal_portletmodifiedassets', methods: ['GET'])]
    public function portletModifiedAssetsAction(
        GetModifiedAssetsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler(), envelope: false);
    }

    #[Route('/portlet-modified-objects', name: 'opendxp_admin_portal_portletmodifiedobjects', methods: ['GET'])]
    public function portletModifiedObjectsAction(
        GetModifiedObjectsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler(), envelope: false);
    }

    #[Route('/portlet-modification-statistics', name: 'opendxp_admin_portal_portletmodificationstatistics', methods: ['GET'])]
    public function portletModificationStatisticsAction(
        GetModificationStatisticsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler(), envelope: false);
    }
}
