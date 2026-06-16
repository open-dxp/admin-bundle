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
use OpenDxp\Bundle\AdminBundle\Handler\Asset\AssetResult;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DownloadAssetHandler
{
    public function __invoke(int $id): AssetResult
    {
        $asset = Asset::getById($id) ?? throw new AssetNotFoundException($id);

        if (!$asset->isAllowed('view')) {
            throw new AccessDeniedHttpException('Not allowed to view asset');
        }

        return new AssetResult($asset);
    }
}
