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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Media;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\ServeVideoPreview\ServeVideoPreviewPayload;
use OpenDxp\Model\Asset;
use OpenDxp\Tool;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ServeVideoPreviewHandler
{
    public function __invoke(ServeVideoPreviewPayload $payload): ServeVideoPreviewResult
    {
        $id = $payload->id;
        $configName = $payload->configName;
        $asset = Asset\Video::getById($id) ?? throw new AssetNotFoundException($id);

        if (!$asset->isAllowed('view')) {
            throw new AccessDeniedHttpException('not allowed to preview');
        }

        $config = Asset\Video\Thumbnail\Config::getByName($configName);
        if (!$config instanceof Asset\Video\Thumbnail\Config) {
            $config = Asset\Video\Thumbnail\Config::getPreviewConfig();
        }

        $thumbnail = $asset->getThumbnail($config, ['mp4']);
        $storagePath = $asset->getRealPath() . '/' . preg_replace('@^' . preg_quote($asset->getPath(), '@') . '@', '', urldecode($thumbnail['formats']['mp4']));

        $storage = Tool\Storage::get('thumbnail');
        if (!$storage->fileExists($storagePath)) {
            throw new NotFoundHttpException('Video thumbnail not found');
        }

        return new ServeVideoPreviewResult(
            $storage->readStream($storagePath),
            $storage->fileSize($storagePath),
        );
    }
}
