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
use OpenDxp\Bundle\AdminBundle\Exception\ElementLockedException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\ClearAssetThumbnail\ClearAssetThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\ClearAssetThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\CreateAssetFolder\CreateAssetFolderPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\CreateAssetFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\DeleteAsset\DeleteAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\DeleteAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetChildren\GetAssetChildrenPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetData\GetAssetDataPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\SaveAsset\SaveAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\UpdateAsset\UpdateAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\UpdateAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\SaveAsset\SaveAssetPayload;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetGridService;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Controller\Traits\ElementEditLockHelperTrait;
use OpenDxp\Model\Element\ElementInterface;
use Override;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Route('/asset')]
#[IsGranted(CorePermission::Assets->value)]
class AssetController extends ElementControllerBase
{
    use ElementEditLockHelperTrait;

    public function __construct(
        ElementServiceInterface $elementService,
        private readonly AssetGridService $assetGridService,
    ) {
        parent::__construct($elementService);
    }

    #[Route('/grid-proxy', name: 'opendxp_admin_asset_gridproxy', methods: ['GET', 'POST', 'PUT'])]
    public function gridProxyAction(
        Request $request,
        EventDispatcherInterface $eventDispatcher,
        CsrfProtectionHandler $csrfProtection,
        #[MapQueryParameter] ?string $language = null,
    ): JsonResponse {
        $allParams = [...$request->request->all(), ...$request->query->all()];
        $effectiveLanguage = $language !== 'default' ? $language : null;

        $filterPrepareEvent = new GenericEvent(null, ['requestParams' => $allParams]);
        $eventDispatcher->dispatch($filterPrepareEvent, AdminEvents::ASSET_LIST_BEFORE_FILTER_PREPARE);
        $allParams = $filterPrepareEvent->getArgument('requestParams');

        if (isset($allParams['data']) && $allParams['data']) {
            $csrfProtection->checkCsrfToken($request);
        }

        return $this->adminJson(
            $this->assetGridService->gridProxy($allParams, $effectiveLanguage)
        );
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
        Request $request,
        #[MapQueryParameter] ?string $id = null,
        #[MapQueryParameter] ?string $type = null,
    ): JsonResponse {
        return parent::deleteInfoAction($handler, $request, $id, $type);
    }

    #[Route('/get-data-by-id', name: 'opendxp_admin_asset_getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetAssetDataPayload $payload,
        GetAssetDataHandler $getAssetData,
    ): JsonResponse {
        try {
            $result = $getAssetData($payload);
        } catch (ElementLockedException $e) {
            return $this->getEditLockResponse($e->getElementId(), $e->getElementType());
        }

        return $this->adminJson($result->data);
    }

    #[Route('/tree-get-children-by-id', name: 'opendxp_admin_asset_treegetchildrenbyid', methods: ['GET'])]
    public function treeGetChildrenByIdAction(
        GetAssetChildrenPayload $payload,
        GetAssetChildrenHandler $getChildren,
        Request $request,
        #[MapQueryParameter] int $inSearch = 0,
    ): JsonResponse {
        $result = $getChildren($payload);

        if ($request->query->has('limit')) {
            return $this->adminJson([
                'offset'   => $result->offset,
                'limit'    => $result->limit,
                'total'    => $result->totalChildCount,
                'overflow' => $payload->filter !== null && ($result->filteredTotalCount > $result->limit),
                'nodes'    => $result->assets,
                'filter'   => $result->filter ?: '',
                'inSearch' => $inSearch,
            ]);
        }

        return $this->adminJson($result->assets);
    }

    #[Route('/add-folder', name: 'opendxp_admin_asset_addfolder', methods: ['POST'])]
    public function addFolderAction(CreateAssetFolderPayload $payload, CreateAssetFolderHandler $createFolder): JsonResponse
    {
        $createFolder($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/delete', name: 'opendxp_admin_asset_delete', methods: ['DELETE'])]
    public function deleteAction(DeleteAssetPayload $payload, DeleteAssetHandler $deleteAsset): JsonResponse
    {
        $result = $deleteAsset($payload);

        if ($result->deleted) {
            return $this->adminJson(ApiResponse::ok(['deleted' => $result->deleted]));
        }

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/update', name: 'opendxp_admin_asset_update', methods: ['PUT'])]
    public function updateAction(UpdateAssetPayload $payload, UpdateAssetHandler $updateAsset): JsonResponse
    {
        $result = $updateAsset($payload);

        return $this->adminJson(ApiResponse::ok(['treeData' => $result->treeData]));
    }

    #[Route('/save', name: 'opendxp_admin_asset_save', methods: ['PUT', 'POST'])]
    public function saveAction(SaveAssetHandler $saveAsset, SaveAssetPayload $payload): JsonResponse
    {
        $result = $saveAsset($payload);

        return $this->adminJson(ApiResponse::ok([
            'data'     => [
                'versionDate'  => $result->versionDate,
                'versionCount' => $result->versionCount,
            ],
            'treeData' => $result->treeData,
        ]));
    }

    #[Route('/clear-thumbnail', name: 'opendxp_admin_asset_clearthumbnail', methods: ['POST'])]
    public function clearThumbnailAction(ClearAssetThumbnailPayload $payload, ClearAssetThumbnailHandler $clearThumbnail): JsonResponse
    {
        $clearThumbnail($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Override]
    protected function getTreeNodeConfig(ElementInterface $element): array
    {
        return $this->elementService->getElementTreeNodeConfig($element);
    }
}
