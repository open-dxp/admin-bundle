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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy\GetAssetChildIds\GetAssetChildIdsPayload;
use OpenDxp\Model\Asset;

final class GetAssetChildIdsHandler
{
    public function __invoke(GetAssetChildIdsPayload $payload): ChildIdsResult
    {
        $sourceId = $payload->sourceId;
        $asset = Asset::getById($sourceId) ?? throw new AssetNotFoundException($sourceId);

        if (!$asset->hasChildren()) {
            return new ChildIdsResult([]);
        }

        $list = new Asset\Listing();
        $list->setCondition('`path` LIKE ?', [$list->escapeLike($asset->getRealFullPath()) . '/%']);
        $list->setOrderKey('LENGTH(`path`)', false);
        $list->setOrder('ASC');

        return new ChildIdsResult($list->loadIdList());
    }
}
