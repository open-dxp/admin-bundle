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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload;

use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\CheckAssetExists\CheckAssetExistsPayload;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CheckAssetExistsHandler
{
    public function __invoke(CheckAssetExistsPayload $payload): bool
    {
        $parentId = $payload->parentId;
        $filename = $payload->filename;
        $dir = $payload->dir;
        $parentAsset = Asset::getById($parentId);
        if (!$parentAsset) {
            throw new NotFoundHttpException('Parent asset not found');
        }

        if ($dir) {
            if (str_contains($dir, '..')) {
                throw new BadRequestHttpException('not allowed');
            }
            $dir = '/' . trim($dir, '/ ');
        }

        return Asset\Service::pathExists($parentAsset->getRealFullPath() . $dir . '/' . $filename);
    }
}