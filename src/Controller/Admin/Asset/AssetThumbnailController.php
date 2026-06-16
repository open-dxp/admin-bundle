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

use DateInterval;
use DateTime;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetDocumentThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetFolderContentPreviewHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetFolderThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetImageThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetVideoThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/asset')]
class AssetThumbnailController extends AdminAbstractController
{
    #[Route('/get-image-thumbnail', name: 'opendxp_admin_asset_getimagethumbnail', methods: ['GET'])]
    public function getImageThumbnailAction(
        GetImageThumbnailHandler $getImageThumbnail,
        Request $request,
        #[MapQueryParameter] ?string $fileinfo = null,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $config = null,
        #[MapQueryParameter] ?array $thumbnail = null,
        #[MapQueryParameter] ?string $treepreview = null,
        #[MapQueryParameter] ?string $origin = null,
        #[MapQueryParameter] ?string $cropPercent = null,
        #[MapQueryParameter] ?string $cropWidth = null,
        #[MapQueryParameter] ?string $cropHeight = null,
        #[MapQueryParameter] ?string $cropTop = null,
        #[MapQueryParameter] ?string $cropLeft = null,
    ): BinaryFileResponse|JsonResponse|StreamedResponse {
        $configDecoded = $config ? $this->decodeJson($config) : null;
        $hasCropPercent = $cropPercent !== null && filter_var($cropPercent, FILTER_VALIDATE_BOOLEAN);

        $result = $getImageThumbnail(
            $id,
            $fileinfo !== null,
            $thumbnail,
            $configDecoded,
            $request->query->all(),
            $treepreview !== null,
            $origin,
            $hasCropPercent,
            $cropWidth,
            $cropHeight,
            $cropTop,
            $cropLeft,
        );

        if ($result->returnLoadingGif) {
            $response = new BinaryFileResponse(OPENDXP_WEB_ROOT . '/bundles/opendxpadmin/img/video-loading.gif');
            $response->headers->set('Cache-Control', 'no-store');

            return $response;
        }

        if ($result->thumbnailResult === null) {
            throw $this->createNotFoundException(sprintf('Tree preview thumbnail not available for asset %s', $id));
        }

        if ($result->returnFileinfo) {
            return $this->adminJson([
                'width' => $result->thumbnailResult->getWidth(),
                'height' => $result->thumbnailResult->getHeight(),
            ]);
        }

        $stream = $result->thumbnailResult->getStream();
        if (!$stream) {
            return new BinaryFileResponse(OPENDXP_WEB_ROOT . '/bundles/opendxpadmin/img/filetype-not-supported.svg');
        }

        $response = new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $result->thumbnailResult->getMimeType(),
            'Access-Control-Allow-Origin' => '*',
        ]);
        $this->addThumbnailCacheHeaders($response);

        return $response;
    }

    #[Route('/get-folder-thumbnail', name: 'opendxp_admin_asset_getfolderthumbnail', methods: ['GET'])]
    #[IsGranted(CorePermission::Assets->value)]
    public function getFolderThumbnailAction(GetFolderThumbnailHandler $getFolderThumbnail, #[MapQueryParameter] ?int $id = null): StreamedResponse
    {
        $result = $getFolderThumbnail($id);

        $response = new StreamedResponse(static function () use ($result): void {
            fpassthru($result->stream);
        }, 200, [
            'Content-Type' => 'image/jpg',
        ]);
        $this->addThumbnailCacheHeaders($response);

        return $response;
    }

    #[Route('/get-video-thumbnail', name: 'opendxp_admin_asset_getvideothumbnail', methods: ['GET'])]
    public function getVideoThumbnailAction(
        GetVideoThumbnailHandler $getVideoThumbnail,
        Request $request,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $path = null,
        #[MapQueryParameter] ?string $time = null,
        #[MapQueryParameter] int $image = 0,
        #[MapQueryParameter] ?string $origin = null,
    ): StreamedResponse {
        $result = $getVideoThumbnail(
            $request->query->has('id') ? $id : null,
            $path,
            $request->query->has('treepreview'),
            $request->query->has('settime'),
            $request->query->has('setimage'),
            $request->query->has('image'),
            $image,
            $time,
            $origin,
            $request->query->all(),
        );

        $response = new StreamedResponse(static function () use ($result): void {
            fpassthru($result->stream);
        }, 200, [
            'Content-Type' => 'image/' . $result->fileExtension,
        ]);
        $this->addThumbnailCacheHeaders($response);

        return $response;
    }

    #[Route('/get-document-thumbnail', name: 'opendxp_admin_asset_getdocumentthumbnail', methods: ['GET'])]
    public function getDocumentThumbnailAction(
        GetDocumentThumbnailHandler $getDocumentThumbnail,
        Request $request,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $treepreview = null,
        #[MapQueryParameter] ?int $page = null,
        #[MapQueryParameter] ?string $origin = null,
    ): BinaryFileResponse|StreamedResponse {
        $result = $getDocumentThumbnail($id, $treepreview !== null, $page, $origin, $request->query->all());

        if ($result->stream) {
            $response = new StreamedResponse(static function () use ($result): void {
                fpassthru($result->stream);
            }, 200, [
                'Content-Type' => 'image/' . $result->fileExtension,
            ]);
        } else {
            $response = new BinaryFileResponse(OPENDXP_WEB_ROOT . '/bundles/opendxpadmin/img/filetype-not-supported.svg');
        }

        $this->addThumbnailCacheHeaders($response);

        return $response;
    }

    #[Route('/get-folder-content-preview', name: 'opendxp_admin_asset_getfoldercontentpreview', methods: ['GET'])]
    #[IsGranted(CorePermission::Assets->value)]
    public function getFolderContentPreviewAction(
        GetFolderContentPreviewHandler $getFolderContentPreview,
        Request $request,
    ): JsonResponse {
        $result = $getFolderContentPreview($request->query->all());

        return $this->adminJson(ApiResponse::ok(['assets' => $result->assets, 'total' => $result->total]));
    }

    private function addThumbnailCacheHeaders(Response $response): void
    {
        $lifetime = 300;
        $date = new DateTime('now');
        $date->add(new DateInterval('PT' . $lifetime . 'S'));

        $response->setMaxAge($lifetime);
        $response->setPublic();
        $response->setExpires($date);
        $response->headers->set('Pragma', '');
    }
}
