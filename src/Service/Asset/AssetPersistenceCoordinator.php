<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Service\Asset;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementDraftService;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Model\Asset;

final class AssetPersistenceCoordinator
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceInterface $elementService,
        private readonly ElementDraftService $elementDraftService,
    ) {}

    public function save(Asset $asset, string $task): AssetPersistenceData
    {
        if ($task === 'session') {
            $this->elementDraftService->saveAsset($asset);
        } else {
            $asset->setUserModification($this->userContext->getAdminUser()->getId());
            $asset->save();
        }

        return new AssetPersistenceData(
            data: [
                'versionDate' => $asset->getModificationDate() ?? 0,
                'versionCount' => $asset->getVersionCount(),
            ],
            treeData: $this->elementService->getElementTreeNodeConfig($asset),
        );
    }
}
