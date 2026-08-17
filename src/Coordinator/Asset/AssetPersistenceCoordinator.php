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

namespace OpenDxp\Bundle\AdminBundle\Coordinator\Asset;

use OpenDxp\Bundle\AdminBundle\Dto\Asset\AssetPersistenceDto;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementDraftService;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementServiceInterface;
use OpenDxp\Model\Asset;

final class AssetPersistenceCoordinator
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceInterface $elementService,
        private readonly ElementDraftService $elementDraftService,
    ) {
    }

    public function save(Asset $asset, string $task): AssetPersistenceDto
    {
        $asset->setUserModification($this->userContext->getAdminUser()->getId());

        if ($task === 'session') {
            $this->elementDraftService->saveAsset($asset);
        } else {
            $asset->save();
        }

        return new AssetPersistenceDto(
            data: [
                'versionDate' => $asset->getModificationDate() ?? 0,
                'versionCount' => $asset->getVersionCount(),
            ],
            treeData: $this->elementService->getElementTreeNodeConfig($asset),
        );
    }
}
