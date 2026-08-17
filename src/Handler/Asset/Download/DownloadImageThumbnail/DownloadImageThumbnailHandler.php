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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadImageThumbnail;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Process\Process;

final class DownloadImageThumbnailHandler
{
    public function __invoke(DownloadImageThumbnailPayload $payload): DownloadImageThumbnailResult
    {
        $id = $payload->id;
        $thumbnailName = $payload->thumbnailName;
        $config = $payload->config;
        $configData = $payload->configData;
        $image = Asset\Image::getById($id) ?? throw new AssetNotFoundException($id);

        if (!$image->isAllowed('view')) {
            throw new AccessDeniedHttpException('Not allowed to view thumbnail');
        }

        $thumbnail = null;
        $thumbnailFile = null;
        $deleteThumbnail = true;

        if ($configData) {
            $thumbnailConfig = new Asset\Image\Thumbnail\Config();
            $thumbnailConfig->setName('opendxp-download-' . $image->getId() . '-' . md5($config ?? ''));

            if ($configData['resize_mode'] === 'scaleByWidth') {
                $thumbnailConfig->addItem('scaleByWidth', ['width' => $configData['width']]);
            } elseif ($configData['resize_mode'] === 'scaleByHeight') {
                $thumbnailConfig->addItem('scaleByHeight', ['height' => $configData['height']]);
            } else {
                $thumbnailConfig->addItem('resize', ['width' => $configData['width'], 'height' => $configData['height']]);
            }

            if (!empty($configData['quality']) && $configData['quality'] <= 100 && $configData['quality'] > 0) {
                $thumbnailConfig->setQuality($configData['quality']);
            }
            if (!empty($configData['format'])) {
                $thumbnailConfig->setFormat($configData['format']);
            }
            $thumbnailConfig->setRasterizeSVG(true);

            if ($thumbnailConfig->getFormat() === 'JPEG') {
                $thumbnailConfig->setPreserveMetaData(true);
                if (empty($configData['quality'])) {
                    $thumbnailConfig->setPreserveColor(true);
                }
            }

            $thumbnail = $image->getThumbnail($thumbnailConfig);
            $thumbnailFile = $thumbnail->getLocalFile();

            $exiftool = \OpenDxp\Tool\Console::getExecutable('exiftool');
            if ($thumbnailConfig->getFormat() === 'JPEG' && $exiftool && isset($configData['dpi']) && $configData['dpi']) {
                $process = new Process([$exiftool, '-overwrite_original', '-xresolution=' . (int)$configData['dpi'], '-yresolution=' . (int)$configData['dpi'], '-resolutionunit=inches', $thumbnailFile]);
                $process->run();
            }
        } elseif ($thumbnailName) {
            $thumbnail = $image->getThumbnail($thumbnailName);
            $deleteThumbnail = false;
        }

        if ($thumbnail) {
            $thumbnailConfig = $thumbnail->getConfig();
            if ($thumbnailConfig->getFormat() === 'SOURCE' && $autoFormatConfigs = $thumbnailConfig->getAutoFormatThumbnailConfigs()) {
                $autoFormatConfig = current($autoFormatConfigs);
                $thumbnail = $image->getThumbnail($autoFormatConfig);
            }
            $thumbnailFile = $thumbnailFile ?: $thumbnail->getLocalFile();

            return new DownloadImageThumbnailResult($image, $thumbnail, $thumbnailFile, $deleteThumbnail);
        }

        throw new AssetNotFoundException($id);
    }
}
