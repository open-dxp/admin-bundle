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

use OpenDxp\Bundle\AdminBundle\Attribute\SessionIdentityAware;
use OpenDxp\Bundle\AdminBundle\Controller\Admin\ElementControllerBase;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\ClearAssetThumbnail\ClearAssetThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\ClearAssetThumbnail\ClearAssetThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\CreateAssetFolder\CreateAssetFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\CreateAssetFolder\CreateAssetFolderPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\DeleteAsset\DeleteAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\DeleteAsset\DeleteAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetData\GetAssetDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetData\GetAssetDataPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GridProxy\GridProxyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GridProxy\GridProxyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\SaveAsset\SaveAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\SaveAsset\SaveAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\TreeGetAssetChildren\TreeGetAssetChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\TreeGetAssetChildren\TreeGetAssetChildrenPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\UpdateAsset\UpdateAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\UpdateAsset\UpdateAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfo\GetDeleteInfoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfo\GetDeleteInfoPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetTreeRoot\GetTreeRootHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetTreeRoot\GetTreeRootPayload;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Security\CorePermission;
use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/asset')]
#[IsGranted(CorePermission::Assets->value)]
class AssetController extends ElementControllerBase
{

    #[Route('/grid-proxy', name: 'opendxp_admin_asset_gridproxy', methods: ['GET', 'POST', 'PUT'])]
    public function gridProxyAction(
        Request $request,
        GridProxyHandler $handler,
        GridProxyPayload $payload,
        CsrfProtectionHandler $csrfProtection,
    ): JsonResponse {
        if (isset($payload->params['data']) && $payload->params['data']) {
            $csrfProtection->checkCsrfToken($request);
        }

        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[Override]
    #[Route('/tree-get-root', name: 'opendxp_admin_asset_treegetroot', methods: ['GET'])]
    public function treeGetRootAction(
        GetTreeRootPayload $payload,
        GetTreeRootHandler $handler,
    ): JsonResponse {
        return parent::treeGetRootAction($payload, $handler);
    }

    #[Override]
    #[Route('/delete-info', name: 'opendxp_admin_asset_deleteinfo', methods: ['GET'])]
    public function deleteInfoAction(
        GetDeleteInfoHandler $handler,
        GetDeleteInfoPayload $payload,
    ): JsonResponse {
        return parent::deleteInfoAction($handler, $payload);
    }

    #[SessionIdentityAware]
    #[Route('/get-data-by-id', name: 'opendxp_admin_asset_getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetAssetDataPayload $payload,
        GetAssetDataHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[Route('/tree-get-children-by-id', name: 'opendxp_admin_asset_treegetchildrenbyid', methods: ['GET'])]
    public function treeGetChildrenByIdAction(
        Request $request,
        TreeGetAssetChildrenPayload $payload,
    ): Response {
        return match ($payload->hasPagination()) {
            true  => $this->forward(self::class . '::treeGetChildrenByIdPaginatedAction', [], $request->query->all()),
            false => $this->forward(self::class . '::treeGetChildrenByIdListAction', [], $request->query->all()),
        };
    }

    #[Route('/tree-get-children-by-id-paginated', name: 'opendxp_admin_asset_treegetchildrenbyidpaginated', methods: ['GET'])]
    public function treeGetChildrenByIdPaginatedAction(
        TreeGetAssetChildrenPayload $payload,
        TreeGetAssetChildrenHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[Route('/tree-get-children-by-id-list', name: 'opendxp_admin_asset_treegetchildrenbyidlist', methods: ['GET'])]
    public function treeGetChildrenByIdListAction(
        TreeGetAssetChildrenPayload $payload,
        TreeGetAssetChildrenHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'nodes');
    }

    #[Route('/add-folder', name: 'opendxp_admin_asset_addfolder', methods: ['POST'])]
    public function addFolderAction(CreateAssetFolderPayload $payload, CreateAssetFolderHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/delete', name: 'opendxp_admin_asset_delete', methods: ['DELETE'])]
    public function deleteAction(DeleteAssetPayload $payload, DeleteAssetHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        if ($result->deleted !== null) {
            return $this->apiJson($result);
        }

        return $this->apiOk();
    }

    #[Route('/update', name: 'opendxp_admin_asset_update', methods: ['PUT'])]
    public function updateAction(UpdateAssetPayload $payload, UpdateAssetHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[SessionIdentityAware]
    #[Route('/save', name: 'opendxp_admin_asset_save', methods: ['PUT', 'POST'])]
    public function saveAction(SaveAssetHandler $handler, SaveAssetPayload $payload): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[Route('/clear-thumbnail', name: 'opendxp_admin_asset_clearthumbnail', methods: ['POST'])]
    public function clearThumbnailAction(ClearAssetThumbnailPayload $payload, ClearAssetThumbnailHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

}
