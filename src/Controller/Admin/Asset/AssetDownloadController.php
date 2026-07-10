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
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\AddFilesToZip\AddFilesToZipPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\AddFilesToZip\AddFilesToZipHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadAsset\DownloadAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadAsset\DownloadAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadImageThumbnail\DownloadImageThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadImageThumbnail\DownloadImageThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadZip\DownloadZipPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadZip\DownloadZipHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\GetDownloadZipJobs\GetDownloadZipJobsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\GetDownloadZipJobs\GetDownloadZipJobsHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/asset')]
#[IsGranted(CorePermission::Assets->value)]
class AssetDownloadController extends AdminAbstractController
{
    #[Route('/download', name: 'opendxp_admin_asset_download', methods: ['GET'])]
    public function downloadAction(DownloadAssetPayload $payload, DownloadAssetHandler $downloadAsset): StreamedResponse
    {
        $result = $downloadAsset($payload);
        $asset = $result->asset;
        $stream = $asset->getStream();

        if (!is_resource($stream)) {
            throw $this->createNotFoundException('Unable to get resource for asset ' . $asset->getId());
        }

        return new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $asset->getMimeType(),
            'Content-Disposition' => sprintf('attachment; filename="%s"', $asset->getFilename()),
            'Content-Length' => $asset->getFileSize(),
        ]);
    }

    #[Route('/download-image-thumbnail', name: 'opendxp_admin_asset_downloadimagethumbnail', methods: ['GET'])]
    public function downloadImageThumbnailAction(
        DownloadImageThumbnailPayload $payload,
        DownloadImageThumbnailHandler $downloadImageThumbnail,
    ): BinaryFileResponse {
        $result = $downloadImageThumbnail($payload);

        $downloadFilename = preg_replace(
            '/\.' . preg_quote(pathinfo($result->image->getFilename(), PATHINFO_EXTENSION), '/') . '$/i',
            '.' . $result->thumbnail->getFileExtension(),
            $result->image->getFilename()
        );

        clearstatcache();

        $response = new BinaryFileResponse($result->localFile);
        $response->headers->set('Content-Type', $result->thumbnail->getMimeType());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $downloadFilename);
        $this->addThumbnailCacheHeaders($response);
        $response->deleteFileAfterSend($result->deleteThumbnail);

        return $response;
    }

    #[Route('/download-as-zip-jobs', name: 'opendxp_admin_asset_downloadaszipjobs', methods: ['GET'])]
    public function downloadAsZipJobsAction(
        GetDownloadZipJobsPayload $payload,
        GetDownloadZipJobsHandler $getZipJobs,
    ): JsonResponse {
        $result = $getZipJobs($payload);

        return $this->adminJson(ApiResponse::ok(['jobs' => $result->jobs, 'jobId' => $result->jobId]));
    }

    #[Route('/download-as-zip-add-files', name: 'opendxp_admin_asset_downloadaszipaddfiles', methods: ['GET'])]
    public function downloadAsZipAddFilesAction(
        AddFilesToZipPayload $payload,
        AddFilesToZipHandler $addFilesToZip,
    ): JsonResponse {
        $addFilesToZip($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    /**
     * Download all assets contained in the folder with parameter id as ZIP file.
     * The suggested filename is either [folder name].zip or assets.zip for the root folder.
     */
    #[Route('/download-as-zip', name: 'opendxp_admin_asset_downloadaszip', methods: ['GET'])]
    public function downloadAsZipAction(
        DownloadZipPayload $payload,
        DownloadZipHandler $downloadZip,
    ): BinaryFileResponse {
        $result = $downloadZip($payload);

        $response = new BinaryFileResponse($result->zipFile);
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $result->suggestedFilename . '.zip');
        $response->deleteFileAfterSend(true);

        return $response;
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
