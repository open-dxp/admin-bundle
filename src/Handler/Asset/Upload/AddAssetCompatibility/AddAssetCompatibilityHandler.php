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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\AddAssetCompatibility;

use Exception;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\AddAsset\AddAssetPayload;
use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetUploadService;

final class AddAssetCompatibilityHandler
{
    public function __construct(private readonly AssetUploadService $assetUploadService)
    {
    }

    public function __invoke(AddAssetPayload $payload): AddAssetCompatibilityResult
    {
        try {
            $asset = $this->assetUploadService->addAsset($payload);
        } catch (Exception $e) {
            throw new AdminOperationFailedException($e->getMessage());
        }

        return new AddAssetCompatibilityResult(
            msg: 'Success',
            id: $asset->getId(),
            fullpath: $asset->getRealFullPath(),
            type: $asset->getType(),
        );
    }
}
