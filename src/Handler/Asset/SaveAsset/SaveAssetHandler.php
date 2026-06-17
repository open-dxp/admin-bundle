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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\SaveAsset;

use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetPersistenceCoordinator;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SaveAssetHandler
{
    public function __construct(
        private readonly AssetPayloadMapper $payloadMapper,
        private readonly AssetPersistenceCoordinator $coordinator,
    ) {}

    public function __invoke(SaveAssetPayload $payload): SaveAssetResult
    {
        $asset = Asset::getById($payload->id) ?? throw new NotFoundHttpException('Asset not found');

        if (!$asset->isAllowed('publish')) {
            throw new AccessDeniedHttpException();
        }

        $this->payloadMapper->applyPayload($payload, $asset);

        return $this->coordinator->save($asset, $payload->task);
    }
}
