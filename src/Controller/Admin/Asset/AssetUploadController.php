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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Asset;

use Exception;
use OpenDxp\Bundle\AdminBundle\Attribute\AsHtmlContentTypeResponse;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\CheckAssetExists\CheckAssetExistsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\CheckAssetExists\CheckAssetExistsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZipFiles\ImportZipFilesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZipFiles\ImportZipFilesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZip\ImportZipPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZip\ImportZipHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ReplaceAsset\ReplaceAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ReplaceAsset\ReplaceAssetHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetUploadService;
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
class AssetUploadController extends AdminAbstractController
{
    public function __construct(
        private readonly AssetUploadService $assetUploadService,
    ) {}

    #[Route('/add-asset', name: 'opendxp_admin_asset_addasset', methods: ['POST'])]
    public function addAssetAction(Request $request): JsonResponse
    {
        try {
            $res = $this->assetUploadService->addAsset($request);
        } catch (Exception $e) {
            throw new AdminOperationFailedException($e->getMessage());
        }

        return $this->adminJson(ApiResponse::ok([
            'asset' => [
                'id' => $res['asset']->getId(),
                'path' => $res['asset']->getFullPath(),
                'type' => $res['asset']->getType(),
            ],
        ]));
    }

    #[AsHtmlContentTypeResponse]
    #[Route('/add-asset-compatibility', name: 'opendxp_admin_asset_addassetcompatibility', methods: ['POST'])]
    public function addAssetCompatibilityAction(Request $request): JsonResponse
    {
        try {
            $res = $this->assetUploadService->addAsset($request);
        } catch (Exception $e) {
            throw new AdminOperationFailedException($e->getMessage());
        }

        return $this->adminJson(ApiResponse::fromBool($res['success'], [
            'msg' => $res['success'] ? 'Success' : 'Error',
            'id' => $res['asset'] ? $res['asset']->getId() : null,
            'fullpath' => $res['asset'] ? $res['asset']->getRealFullPath() : null,
            'type' => $res['asset'] ? $res['asset']->getType() : null,
        ]));
    }

    #[Route('/exists', name: 'opendxp_admin_asset_exists', methods: ['GET'])]
    public function existsAction(
        CheckAssetExistsPayload $payload,
        CheckAssetExistsHandler $checkAssetExists,
    ): JsonResponse {
        return new JsonResponse([
            'exists' => $checkAssetExists($payload),
        ]);
    }

    #[AsHtmlContentTypeResponse]
    #[Route('/replace-asset', name: 'opendxp_admin_asset_replaceasset', methods: ['POST', 'PUT'])]
    public function replaceAssetAction(ReplaceAssetPayload $payload, ReplaceAssetHandler $replaceAsset): JsonResponse
    {
        $asset = $replaceAsset($payload);

        return $this->adminJson(ApiResponse::ok(['id' => $asset->getId(), 'path' => $asset->getRealFullPath()]));
    }

    #[Route('/import-zip', name: 'opendxp_admin_asset_importzip', methods: ['POST'])]
    public function importZipAction(
        ImportZipPayload $payload,
        ImportZipHandler $importZip,
    ): Response {
        $importResult = $importZip($payload);

        return new Response($this->encodeJson(ApiResponse::ok(['jobs' => $importResult->jobs, 'jobId' => $importResult->jobId])));
    }

    #[Route('/import-zip-files', name: 'opendxp_admin_asset_importzipfiles', methods: ['POST'])]
    public function importZipFilesAction(ImportZipFilesPayload $payload, ImportZipFilesHandler $importZipFiles): JsonResponse
    {
        $importZipFiles($payload);

        return $this->adminJson(ApiResponse::ok());
    }
}
