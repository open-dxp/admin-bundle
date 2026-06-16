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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings;

use OpenDxp\Model\Asset;

final class GetDownloadableThumbnailsHandler
{
    public function __invoke(): GetDownloadableThumbnailsResult
    {
        $thumbnails = [];
        $list = new Asset\Image\Thumbnail\Config\Listing();
        $list->setFilter(fn (Asset\Image\Thumbnail\Config $config) => $config->isDownloadable());

        foreach ($list->getThumbnails() as $item) {
            $thumbnails[] = [
                'id' => $item->getName(),
                'text' => $item->getName(),
            ];
        }

        return new GetDownloadableThumbnailsResult(thumbnails: $thumbnails);
    }
}
