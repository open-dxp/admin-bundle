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
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetDocumentThumbnail\GetDocumentThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetDocumentThumbnail\GetDocumentThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetFolderContentPreview\GetFolderContentPreviewHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetFolderContentPreview\GetFolderContentPreviewPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetFolderThumbnail\GetFolderThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetFolderThumbnail\GetFolderThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetImageThumbnailFileinfo\GetImageThumbnailFileinfoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetImageThumbnail\GetImageThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetImageThumbnail\GetImageThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetVideoThumbnail\GetVideoThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetVideoThumbnail\GetVideoThumbnailPayload;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        GetImageThumbnailPayload $payload,
        GetImageThumbnailHandler $getImageThumbnail,
    ): BinaryFileResponse|StreamedResponse {
        $result = $getImageThumbnail($payload);

        if ($result->thumbnailResult === null) {
            $response = new BinaryFileResponse(OPENDXP_WEB_ROOT . '/bundles/opendxpadmin/img/video-loading.gif');
            $response->headers->set('Cache-Control', 'no-store');

            return $response;
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

    #[Route('/get-image-thumbnail/fileinfo', name: 'opendxp_admin_asset_getimagethumbnail_fileinfo', methods: ['GET'])]
    public function getImageThumbnailFileinfoAction(
        GetImageThumbnailPayload $payload,
        GetImageThumbnailFileinfoHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/get-folder-thumbnail', name: 'opendxp_admin_asset_getfolderthumbnail', methods: ['GET'])]
    #[IsGranted(CorePermission::Assets->value)]
    public function getFolderThumbnailAction(GetFolderThumbnailPayload $payload, GetFolderThumbnailHandler $handler): StreamedResponse
    {
        $result = $handler($payload);

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
        GetVideoThumbnailPayload $payload,
        GetVideoThumbnailHandler $handler,
    ): StreamedResponse {
        $result = $handler($payload);

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
        GetDocumentThumbnailPayload $payload,
        GetDocumentThumbnailHandler $getDocumentThumbnail,
    ): BinaryFileResponse|StreamedResponse {
        $result = $getDocumentThumbnail($payload);

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
        GetFolderContentPreviewPayload $payload,
        GetFolderContentPreviewHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
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
