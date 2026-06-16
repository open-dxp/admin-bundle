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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Model\Asset;

final class DownloadZipHandler
{
    public function __invoke(int $id, string $jobId): DownloadZipResult
    {
        $asset = Asset::getById($id) ?? throw new AssetNotFoundException($id);
        $zipFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/download-zip-' . $jobId . '.zip';
        $suggestedFilename = $asset->getFilename() ?: 'assets';

        return new DownloadZipResult($zipFile, $suggestedFilename);
    }
}
