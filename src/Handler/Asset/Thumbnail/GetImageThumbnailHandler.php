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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Messenger\AssetPreviewImageMessage;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

final class GetImageThumbnailHandler
{
    public function __construct(private readonly MessageBusInterface $messageBus) {}

    public function __invoke(
        int $id,
        bool $hasFileinfo,
        ?array $thumbnailParam,
        ?array $configDecoded,
        array $queryAll,
        bool $hasThumbnailPreview,
        ?string $origin,
        bool $hasCropPercent,
        ?string $cropWidth,
        ?string $cropHeight,
        ?string $cropTop,
        ?string $cropLeft,
    ): GetImageThumbnailResult {
        $image = Asset\Image::getById($id) ?? throw new AssetNotFoundException($id);

        if (!$image->isAllowed('view')) {
            throw new AccessDeniedHttpException('not allowed to view thumbnail');
        }

        $thumbnailConfig = null;

        if ($thumbnailParam) {
            $thumbnailConfig = $image->getThumbnail($thumbnailParam)->getConfig();
        }
        if (!$thumbnailConfig) {
            if ($configDecoded) {
                $thumbnailConfig = $image->getThumbnail($configDecoded)->getConfig();
            } else {
                $thumbnailConfig = $image->getThumbnail($queryAll)->getConfig();
            }
        } else {
            $thumbnailConfig->setHighResolution(1);
        }

        $format = strtolower($thumbnailConfig->getFormat());
        if ($format === 'source' || $format === 'print') {
            $thumbnailConfig->setFormat('PNG');
            $thumbnailConfig->setRasterizeSVG(true);
        }

        if ($hasThumbnailPreview) {
            $thumbnailConfig = Asset\Image\Thumbnail\Config::getPreviewConfig();
            if (!$image->getThumbnail($thumbnailConfig)->exists()) {
                $this->messageBus->dispatch(new AssetPreviewImageMessage($image->getId()));

                return new GetImageThumbnailResult($image, null, $origin === 'folderPreview', false);
            }
        }

        if ($hasCropPercent) {
            $thumbnailConfig->addItemAt(0, 'cropPercent', [
                'width' => $cropWidth,
                'height' => $cropHeight,
                'y' => $cropTop,
                'x' => $cropLeft,
            ]);

            $thumbnailConfig->generateAutoName();
        }

        $thumbnailResult = $image->getThumbnail($thumbnailConfig);

        return new GetImageThumbnailResult($image, $thumbnailResult, false, $hasFileinfo);
    }
}
