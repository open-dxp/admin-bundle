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

use OpenDxp\Bundle\AdminBundle\Controller\Admin\ElementControllerBase;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\ClearAssetThumbnail\ClearAssetThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\ClearAssetThumbnail\ClearAssetThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfo\GetDeleteInfoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfo\GetDeleteInfoPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\CreateAssetFolder\CreateAssetFolderPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\CreateAssetFolder\CreateAssetFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\DeleteAsset\DeleteAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\DeleteAsset\DeleteAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetChildren\GetAssetChildrenPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetChildren\GetAssetChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetData\GetAssetDataPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetData\GetAssetDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GridProxy\GridProxyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GridProxy\GridProxyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\SaveAsset\SaveAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\UpdateAsset\UpdateAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\UpdateAsset\UpdateAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\SaveAsset\SaveAssetPayload;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Model\Element\ElementInterface;
use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/asset')]
#[IsGranted(CorePermission::Assets->value)]
class AssetController extends ElementControllerBase
{
    public function __construct(
        ElementServiceInterface $elementService,
    ) {
        parent::__construct($elementService);
    }

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

        return $this->adminJson($handler($payload)->data);
    }

    #[Override]
    #[Route('/tree-get-root', name: 'opendxp_admin_asset_treegetroot', methods: ['GET'])]
    public function treeGetRootAction(
        #[MapQueryParameter] ?string $elementType = null,
        #[MapQueryParameter(flags: FILTER_NULL_ON_FAILURE)] ?int $id = null,
    ): JsonResponse {
        return parent::treeGetRootAction($elementType, $id);
    }

    #[Override]
    #[Route('/delete-info', name: 'opendxp_admin_asset_deleteinfo', methods: ['GET'])]
    public function deleteInfoAction(
        GetDeleteInfoHandler $handler,
        GetDeleteInfoPayload $payload,
    ): JsonResponse {
        return parent::deleteInfoAction($handler, $payload);
    }

    #[Route('/get-data-by-id', name: 'opendxp_admin_asset_getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetAssetDataPayload $payload,
        GetAssetDataHandler $handler,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson($result->data);
    }

    #[Route('/tree-get-children-by-id', name: 'opendxp_admin_asset_treegetchildrenbyid', methods: ['GET'])]
    public function treeGetChildrenByIdAction(
        GetAssetChildrenPayload $payload,
        GetAssetChildrenHandler $handler,
    ): JsonResponse {
        $result = $handler($payload);

        if ($payload->hasLimit) {
            return $this->adminJson([
                'offset'   => $result->offset,
                'limit'    => $result->limit,
                'total'    => $result->totalChildCount,
                'overflow' => $payload->filter !== null && ($result->filteredTotalCount > $result->limit),
                'nodes'    => $result->assets,
                'filter'   => $result->filter ?: '',
                'inSearch' => $payload->inSearch,
            ]);
        }

        return $this->adminJson($result->assets);
    }

    #[Route('/add-folder', name: 'opendxp_admin_asset_addfolder', methods: ['POST'])]
    public function addFolderAction(CreateAssetFolderPayload $payload, CreateAssetFolderHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/delete', name: 'opendxp_admin_asset_delete', methods: ['DELETE'])]
    public function deleteAction(DeleteAssetPayload $payload, DeleteAssetHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        if ($result->deleted) {
            return $this->adminJson(ApiResponse::ok(['deleted' => $result->deleted]));
        }

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/update', name: 'opendxp_admin_asset_update', methods: ['PUT'])]
    public function updateAction(UpdateAssetPayload $payload, UpdateAssetHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['treeData' => $result->treeData]));
    }

    #[Route('/save', name: 'opendxp_admin_asset_save', methods: ['PUT', 'POST'])]
    public function saveAction(SaveAssetHandler $handler, SaveAssetPayload $payload): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok([
            'data'     => [
                'versionDate'  => $result->versionDate,
                'versionCount' => $result->versionCount,
            ],
            'treeData' => $result->treeData,
        ]));
    }

    #[Route('/clear-thumbnail', name: 'opendxp_admin_asset_clearthumbnail', methods: ['POST'])]
    public function clearThumbnailAction(ClearAssetThumbnailPayload $payload, ClearAssetThumbnailHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Override]
    protected function getTreeNodeConfig(ElementInterface $element): array
    {
        return $this->elementService->getElementTreeNodeConfig($element);
    }
}
