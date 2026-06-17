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
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\GetVideoPreview\GetVideoPreviewPayload;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class GetVideoPreviewHandler
{
    public function __invoke(GetVideoPreviewPayload $payload): PreviewVideoResult
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
        $isFinished = $thumbnail && $thumbnail['status'] === 'finished';

        return new PreviewVideoResult($asset, $thumbnail, $config->getName(), $isFinished);
    }
}
