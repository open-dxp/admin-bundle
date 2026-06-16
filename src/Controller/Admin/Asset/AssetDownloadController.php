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
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\AddFilesToZipHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadImageThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadZipHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\GetDownloadZipJobsHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
    public function downloadAction(DownloadAssetHandler $downloadAsset, #[MapQueryParameter] int $id): StreamedResponse
    {
        $result = $downloadAsset($id);
        $asset = $result->asset;
        $stream = $asset->getStream();

        if (!is_resource($stream)) {
            throw $this->createNotFoundException('Unable to get resource for asset ' . $asset->getId());
        }

        return new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $asset->getMimeType(),
            'Content-Disposition' => sprintf('attachment; filename: "%s"', $asset->getFilename()),
            'Content-Length' => $asset->getFileSize(),
        ]);
    }

    #[Route('/download-image-thumbnail', name: 'opendxp_admin_asset_downloadimagethumbnail', methods: ['GET'])]
    public function downloadImageThumbnailAction(
        DownloadImageThumbnailHandler $downloadImageThumbnail,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $thumbnail = null,
        #[MapQueryParameter] ?string $config = null,
        #[MapQueryParameter] ?string $type = null,
    ): BinaryFileResponse {
        $configData = null;
        if ($config !== null) {
            $configData = $this->decodeJson($config);
        } elseif ($type !== null) {
            $predefined = [
                'web'    => ['resize_mode' => 'scaleByWidth', 'width' => 3500, 'dpi' => 72,  'format' => 'JPEG', 'quality' => 85],
                'print'  => ['resize_mode' => 'scaleByWidth', 'width' => 6000, 'dpi' => 300, 'format' => 'JPEG', 'quality' => 95],
                'office' => ['resize_mode' => 'scaleByWidth', 'width' => 1190, 'dpi' => 144, 'format' => 'JPEG', 'quality' => 90],
            ];
            $configData = $predefined[$type];
        }

        $result = $downloadImageThumbnail($id, $thumbnail, $config, $configData);

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
        GetDownloadZipJobsHandler $getZipJobs,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] string $selectedIds = '',
    ): JsonResponse {
        $result = $getZipJobs($id, $selectedIds);

        return $this->adminJson(ApiResponse::ok(['jobs' => $result->jobs, 'jobId' => $result->jobId]));
    }

    #[Route('/download-as-zip-add-files', name: 'opendxp_admin_asset_downloadaszipaddfiles', methods: ['GET'])]
    public function downloadAsZipAddFilesAction(
        AddFilesToZipHandler $addFilesToZip,
        #[MapQueryParameter] ?string $jobId = null,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $selectedIds = null,
        #[MapQueryParameter] int $offset = 0,
        #[MapQueryParameter] int $limit = 0,
    ): JsonResponse {
        $addFilesToZip($id, $selectedIds, $offset, $limit, (string) $jobId);

        return $this->adminJson(ApiResponse::ok());
    }

    /**
     * Download all assets contained in the folder with parameter id as ZIP file.
     * The suggested filename is either [folder name].zip or assets.zip for the root folder.
     */
    #[Route('/download-as-zip', name: 'opendxp_admin_asset_downloadaszip', methods: ['GET'])]
    public function downloadAsZipAction(
        DownloadZipHandler $downloadZip,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $jobId = null,
    ): BinaryFileResponse {
        $result = $downloadZip($id, (string) $jobId);

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
