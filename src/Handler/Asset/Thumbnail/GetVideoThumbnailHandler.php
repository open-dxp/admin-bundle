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
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetVideoThumbnail\GetVideoThumbnailPayload;
use OpenDxp\Messenger\AssetPreviewImageMessage;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

final class GetVideoThumbnailHandler
{
    public function __construct(private readonly MessageBusInterface $messageBus) {}

    public function __invoke(GetVideoThumbnailPayload $payload): GetVideoThumbnailResult
    {
        $id = $payload->id;
        $path = $payload->path;
        $hasThumbnailPreview = $payload->hasThumbnailPreview;
        $hasSetTime = $payload->hasSetTime;
        $hasSetImage = $payload->hasSetImage;
        $hasImage = $payload->hasImage;
        $imageId = $payload->imageId;
        $time = $payload->time;
        $origin = $payload->origin;
        $queryAll = $payload->queryAll;
        $video = null;

        if ($id !== null) {
            $video = Asset\Video::getById($id);
        } elseif ($path !== null) {
            $video = Asset\Video::getByPath($path);
        }

        if (!$video instanceof Asset\Video) {
            throw new NotFoundHttpException('could not load video asset');
        }

        if (!$video->isAllowed('view')) {
            throw new AccessDeniedHttpException('not allowed to view thumbnail');
        }

        $thumbnailConfig = $queryAll;
        if ($hasThumbnailPreview) {
            $thumbnailConfig = Asset\Image\Thumbnail\Config::getPreviewConfig();
        }

        $timeInt = is_numeric($time) ? (int) $time : null;

        if ($hasSetTime) {
            $video->removeCustomSetting('image_thumbnail_asset');
            $video->setCustomSetting('image_thumbnail_time', $timeInt);
            $video->save();
        }

        $image = null;
        if ($hasImage) {
            $image = Asset\Image::getById($imageId) ?? throw new AssetNotFoundException($imageId);
        }

        if ($hasSetImage && $image) {
            $video->removeCustomSetting('image_thumbnail_time');
            $video->setCustomSetting('image_thumbnail_asset', $image->getId());
            $video->save();
        }

        $thumb = $video->getImageThumbnail($thumbnailConfig, $timeInt, $image);

        if ($origin === 'treeNode' && !$thumb->exists()) {
            $this->messageBus->dispatch(new AssetPreviewImageMessage($video->getId()));
            throw new NotFoundHttpException(sprintf('Tree preview thumbnail not available for asset %s', $video->getId()));
        }

        $stream = $thumb->getStream();
        if (!$stream) {
            throw new NotFoundHttpException('Unable to get video thumbnail for video ' . $video->getId());
        }

        return new GetVideoThumbnailResult($stream, $thumb->getFileExtension());
    }
}
