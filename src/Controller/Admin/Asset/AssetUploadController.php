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

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\CheckAssetExistsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZipFilesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZipHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ReplaceAssetHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetUploadService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
        $res = $this->assetUploadService->addAsset($request);

        if ($res['success']) {
            return $this->adminJson(ApiResponse::ok([
                'asset' => [
                    'id' => $res['asset']->getId(),
                    'path' => $res['asset']->getFullPath(),
                    'type' => $res['asset']->getType(),
                ],
            ]));
        }

        throw new BadRequestHttpException();
    }

    #[Route('/add-asset-compatibility', name: 'opendxp_admin_asset_addassetcompatibility', methods: ['POST'])]
    public function addAssetCompatibilityAction(Request $request): JsonResponse
    {
        $res = $this->assetUploadService->addAsset($request);

        $response = $this->adminJson(ApiResponse::fromBool($res['success'], [
            'msg' => $res['success'] ? 'Success' : 'Error',
            'id' => $res['asset'] ? $res['asset']->getId() : null,
            'fullpath' => $res['asset'] ? $res['asset']->getRealFullPath() : null,
            'type' => $res['asset'] ? $res['asset']->getType() : null,
        ]));
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/exists', name: 'opendxp_admin_asset_exists', methods: ['GET'])]
    public function existsAction(
        CheckAssetExistsHandler $checkAssetExists,
        #[MapQueryParameter] int $parentId,
        #[MapQueryParameter] string $filename = '',
        #[MapQueryParameter] string $dir = '',
    ): JsonResponse {
        return new JsonResponse([
            'exists' => $checkAssetExists($parentId, $filename, $dir),
        ]);
    }

    #[Route('/replace-asset', name: 'opendxp_admin_asset_replaceasset', methods: ['POST', 'PUT'])]
    public function replaceAssetAction(ReplaceAssetHandler $replaceAsset, Request $request, #[MapQueryParameter] int $id): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('Filedata');

        $asset = $replaceAsset($id, $file->getPathname(), $file->getClientOriginalName());

        $response = $this->adminJson(ApiResponse::ok(['id' => $asset->getId(), 'path' => $asset->getRealFullPath()]));
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/import-zip', name: 'opendxp_admin_asset_importzip', methods: ['POST'])]
    public function importZipAction(
        ImportZipHandler $importZip,
        Request $request,
        #[MapQueryParameter] int $parentId = 0,
        #[MapQueryParameter] ?string $allowOverwrite = null,
    ): Response {
        if (!$request->files->has('Filedata')) {
            throw new BadRequestHttpException('Something went wrong, please check upload_max_filesize and post_max_size in your php.ini as well as the write permissions on the file system');
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('Filedata');
        if (!is_file($file->getPathname())) {
            throw new BadRequestHttpException('Something went wrong, please check upload_max_filesize and post_max_size in your php.ini as well as the write permissions on the file system');
        }

        $importResult = $importZip($parentId, $file->getPathname(), $allowOverwrite);

        return new Response($this->encodeJson(ApiResponse::ok(['jobs' => $importResult->jobs, 'jobId' => $importResult->jobId])));
    }

    #[Route('/import-zip-files', name: 'opendxp_admin_asset_importzipfiles', methods: ['POST'])]
    public function importZipFilesAction(ImportZipFilesHandler $importZipFiles, Request $request): JsonResponse
    {
        $importZipFiles(
            (int) $request->request->get('parentId'),
            (string) $request->request->get('jobId'),
            (int) $request->request->get('offset'),
            (int) $request->request->get('limit'),
            $request->request->get('allowOverwrite') === 'true',
            (bool) $request->request->get('last'),
            );

        return $this->adminJson(ApiResponse::ok());
    }
}
