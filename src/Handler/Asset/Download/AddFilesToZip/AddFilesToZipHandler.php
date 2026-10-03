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

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\AddFilesToZip;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetDownloadService;
use OpenDxp\Model\Asset;
use ZipArchive;

final class AddFilesToZipHandler
{
    public function __construct(private readonly AssetDownloadService $assetDownloadService)
    {
    }

    public function __invoke(AddFilesToZipPayload $payload): void
    {
        $zipFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/download-zip-' . $payload->jobId . '.zip';

        $asset = Asset::getById($payload->id) ?? throw new AssetNotFoundException($payload->id);
        if (!$asset->isAllowed('view')) {
            throw new AdminOperationFailedException('');
        }

        $thumbnailConfig = $payload->thumbnail === null
            ? null
            : $this->assetDownloadService->getDownloadableThumbnailConfig($payload->thumbnail);

        $zip = new ZipArchive();
        $zipState = is_file($zipFile) ? $zip->open($zipFile) : $zip->open($zipFile, ZipArchive::CREATE);
        if ($zipState !== true) {
            throw new AdminOperationFailedException('Failed to open zip archive: ' . $zipFile);
        }

        $assetList = $thumbnailConfig === null
            ? $this->assetDownloadService->getZipFileListing($asset, $payload->selectedIds)
            : $this->assetDownloadService->getZipImageListing($asset, $payload->selectedIds);
        $assetList->setOffset($payload->offset);
        $assetList->setLimit($payload->limit);

        foreach ($assetList as $a) {
            if (!$a->isAllowed('view') || $a instanceof Asset\Folder) {
                continue;
            }

            $entryName = preg_replace('@^' . preg_quote($asset->getRealPath(), '@') . '@i', '', $a->getRealFullPath());

            if ($thumbnailConfig !== null && $a instanceof Asset\Image) {
                $thumbnail = $this->assetDownloadService->getThumbnail($a, $thumbnailConfig);
                $zip->addFile($thumbnail->getLocalFile(), $this->thumbnailEntryName($zip, $entryName, $thumbnail, $a));

                continue;
            }

            $zip->addFile($a->getLocalFile(), $entryName);
        }

        $zip->close();
    }

    private function thumbnailEntryName(
        ZipArchive $zip,
        string $entryName,
        Asset\Image\ThumbnailInterface $thumbnail,
        Asset\Image $image,
    ): string {
        $directory = pathinfo($entryName, PATHINFO_DIRNAME);
        $base = ($directory === '.' || $directory === '' ? '' : $directory . '/') . pathinfo($entryName, PATHINFO_FILENAME);
        $extension = $thumbnail->getFileExtension();
        $name = $base . '.' . $extension;

        return $zip->locateName($name) === false ? $name : sprintf('%s-%d.%s', $base, $image->getId(), $extension);
    }
}
