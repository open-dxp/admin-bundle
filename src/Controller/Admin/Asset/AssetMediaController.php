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
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadAsset\DownloadAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\GetAssetText\GetAssetTextPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\GetAssetTextHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\GetDocumentPreview\GetDocumentPreviewPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\GetDocumentPreviewHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\GetVideoPreview\GetVideoPreviewPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\GetVideoPreviewHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\ServeVideoPreview\ServeVideoPreviewPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\ServeVideoPreviewHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Model\Asset\Enum\PdfScanStatus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/asset')]
#[IsGranted(CorePermission::Assets->value)]
class AssetMediaController extends AdminAbstractController
{
    #[Route('/get-asset', name: 'opendxp_admin_asset_getasset', methods: ['GET'])]
    public function getAssetAction(DownloadAssetPayload $payload, DownloadAssetHandler $downloadAsset): StreamedResponse
    {
        $result = $downloadAsset($payload);
        $asset = $result->asset;
        $stream = $asset->getStream();

        if (!is_resource($stream)) {
            throw $this->createNotFoundException('Unable to get resource for asset ' . $asset->getId());
        }

        $response = new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $asset->getMimeType(),
            'Access-Control-Allow-Origin' => '*',
        ]);
        $this->addThumbnailCacheHeaders($response);

        return $response;
    }

    #[Route('/get-preview-document', name: 'opendxp_admin_asset_getpreviewdocument', methods: ['GET'])]
    public function getPreviewDocumentAction(GetDocumentPreviewPayload $payload, GetDocumentPreviewHandler $getDocumentPreview): StreamedResponse|Response
    {
        $result = $getDocumentPreview($payload);
        $asset = $result->asset;

        if ($result->thumbnailPath !== null) {
            return $this->render('@OpenDxpAdmin/admin/asset/get_preview_pdf_open_in_new_tab.html.twig', [
                'thumbnailPath' => $result->thumbnailPath,
                'assetPath' => $result->assetPath,
            ]);
        }

        if ($result->scanStatus === PdfScanStatus::IN_PROGRESS) {
            return $this->render('@OpenDxpAdmin/admin/asset/get_preview_pdf_in_progress.html.twig');
        }

        if ($result->scanStatus === PdfScanStatus::UNSAFE) {
            return $this->render('@OpenDxpAdmin/admin/asset/get_preview_pdf_unsafe.html.twig');
        }

        if ($result->stream) {
            return new StreamedResponse(static function () use ($result): void {
                fpassthru($result->stream);
            }, 200, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        throw $this->createNotFoundException('Unable to get preview for asset ' . $asset->getId());
    }

    #[Route('/get-preview-video', name: 'opendxp_admin_asset_getpreviewvideo', methods: ['GET'])]
    public function getPreviewVideoAction(
        GetVideoPreviewPayload $payload,
        GetVideoPreviewHandler $getVideoPreview,
    ): Response {
        $result = $getVideoPreview($payload);
        $previewData = [
            'asset' => $result->asset,
            'thumbnail' => $result->thumbnail,
            'config' => $result->configName,
        ];

        if ($result->thumbnail && $result->isFinished) {
            return $this->render('@OpenDxpAdmin/admin/asset/get_preview_video_display.html.twig', $previewData);
        }

        return $this->render('@OpenDxpAdmin/admin/asset/get_preview_video_error.html.twig', $previewData);
    }

    #[Route('/serve-video-preview', name: 'opendxp_admin_asset_servevideopreview', methods: ['GET'])]
    public function serveVideoPreviewAction(
        ServeVideoPreviewPayload $payload,
        ServeVideoPreviewHandler $serveVideoPreview,
    ): StreamedResponse {
        $result = $serveVideoPreview($payload);

        return new StreamedResponse(static function () use ($result): void {
            fpassthru($result->stream);
        }, 200, [
            'Content-Type' => 'video/mp4',
            'Content-Length' => $result->fileSize,
            'Accept-Ranges' => 'bytes',
        ]);
    }

    #[Route('/get-text', name: 'opendxp_admin_asset_gettext', methods: ['GET'])]
    public function getTextAction(
        GetAssetTextPayload $payload,
        GetAssetTextHandler $getAssetText,
    ): JsonResponse {
        $result = $getAssetText($payload);

        return $this->adminJson(ApiResponse::ok(['text' => $result->text]));
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
