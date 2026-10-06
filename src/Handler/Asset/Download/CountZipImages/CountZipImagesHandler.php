<?php

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\CountZipImages;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetDownloadService;
use OpenDxp\Model\Asset;

final readonly class CountZipImagesHandler
{
    public function __construct(private AssetDownloadService $assetDownloadService)
    {
    }

    public function __invoke(CountZipImagesPayload $payload): CountZipImagesResult
    {
        $asset = Asset::getById($payload->id) ?? throw new AssetNotFoundException($payload->id);

        if (!$asset->isAllowed('view')) {
            return new CountZipImagesResult(0);
        }

        $assetList = $this->assetDownloadService->getZipImageListing($asset, $payload->selectedIds);

        return new CountZipImagesResult($assetList->getTotalCount());
    }
}
