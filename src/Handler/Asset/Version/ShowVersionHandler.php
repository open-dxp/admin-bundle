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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Version;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetVersionNotFoundException;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ShowVersionHandler
{
    private const string PDF_MIMETYPE = 'application/pdf';

    public function __invoke(int $versionId): ShowVersionResult
    {
        $version = Version::getById($versionId)
            ?? throw new AssetVersionNotFoundException($versionId);

        $asset = $version->loadData();
        if (!$asset instanceof Asset) {
            throw new AssetVersionNotFoundException($versionId);
        }

        if (!$asset->isAllowed('versions')) {
            throw new AccessDeniedHttpException();
        }

        if ($asset instanceof Asset\Document && $asset->getMimeType() === self::PDF_MIMETYPE) {
            return new ShowVersionResult(
                asset: $asset,
                version: $version,
                isPdf: true,
                pdfPath: $asset->getRealFullPath(),
            );
        }

        return new ShowVersionResult(asset: $asset, version: $version);
    }
}
