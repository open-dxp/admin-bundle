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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\GetDocumentPreview;

use Exception;
use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\GetDocumentPreview\GetDocumentPreviewPayload;
use OpenDxp\Config;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Asset\Enum\PdfScanStatus;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class GetDocumentPreviewHandler
{
    private const string PDF_MIMETYPE = 'application/pdf';

    public function __invoke(GetDocumentPreviewPayload $payload): PreviewDocumentResult
    {
        $id = $payload->id;
        $asset = Asset\Document::getById($id);
        if (!$asset instanceof Asset\Document) {
            throw new AssetNotFoundException($id);
        }

        if (!$asset->isAllowed('view')) {
            throw new AccessDeniedHttpException('Access to asset ' . $asset->getId() . ' denied');
        }

        $scanStatus = null;
        $thumbnailPath = null;
        $stream = null;

        if ($asset->getMimeType() === self::PDF_MIMETYPE) {
            $scanStatus = $this->getScanStatus($asset);
            $openPdfConfig = Config::getSystemConfiguration('assets')['document']['open_pdf_in_new_tab'];

            $openInNewTab = $openPdfConfig === 'all-pdfs'
                || ($openPdfConfig === 'only-unsafe' && $scanStatus === PdfScanStatus::UNSAFE);

            if ($openInNewTab) {
                $thumbnail = $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig());
                $thumbnailPath = $thumbnail->getPath();

                return new PreviewDocumentResult($asset, $scanStatus, $thumbnailPath, $asset->getRealFullPath(), null);
            }
        }

        if ($scanStatus === null || ($scanStatus !== PdfScanStatus::IN_PROGRESS && $scanStatus !== PdfScanStatus::UNSAFE)) {
            $stream = $this->getPreviewPdf($asset);
        }

        return new PreviewDocumentResult($asset, $scanStatus, null, $asset->getRealFullPath(), $stream);
    }

    private function getScanStatus(Asset\Document $asset): ?PdfScanStatus
    {
        if (!Config::getSystemConfiguration('assets')['document']['scan_pdf']) {
            return null;
        }

        $scanStatus = $asset->getScanStatus();
        if (!$scanStatus instanceof PdfScanStatus) {
            $asset->addToUpdateTaskQueue();

            return PdfScanStatus::IN_PROGRESS;
        }

        return $scanStatus;
    }

    private function getPreviewPdf(Asset\Document $asset): mixed
    {
        $stream = null;

        if ($asset->getMimeType() === self::PDF_MIMETYPE) {
            $stream = $asset->getStream();
        }

        if (!$stream && $asset->getPageCount() && \OpenDxp\Document::isAvailable() && \OpenDxp\Document::isFileTypeSupported($asset->getFilename())) {
            try {
                $document = \OpenDxp\Document::getInstance();
                $stream = $document->getPdf($asset);
            } catch (Exception) {
                // nothing to do
            }
        }

        return $stream;
    }
}
