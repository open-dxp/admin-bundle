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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset;

use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\User;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class UpdateAssetHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,private readonly ElementServiceInterface $elementService) {}

    public function __invoke(int $assetId, array $updateData): UpdateAssetResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $asset = Asset::getById($assetId);
        $allowUpdate = true;

        if ($asset->isAllowed('settings')) {
            $asset->setUserModification($adminUser->getId());

            if (isset($updateData['parentId']) && $updateData['parentId']) {
                $parentAsset = Asset::getById((int) $updateData['parentId']);

                if ($asset->getParentId() !== $parentAsset->getId()) {
                    if (!$parentAsset->isAllowed('create')) {
                        throw new RuntimeException('Prevented moving asset - no create permission on new parent.');
                    }

                    $intendedPath = $parentAsset->getRealPath();
                    $pKey = $parentAsset->getKey();
                    if (!empty($pKey)) {
                        $intendedPath .= $parentAsset->getKey() . '/';
                    }

                    if (Asset::getByPath($intendedPath . $asset->getKey()) != null) {
                        $allowUpdate = false;
                    }

                    if ($asset->isLocked()) {
                        $allowUpdate = false;
                    }
                }
            }

            if ($allowUpdate) {
                if (isset($updateData['filename']) && $updateData['filename'] != $asset->getFilename() && !$asset->isAllowed('rename')) {
                    unset($updateData['filename']);
                    Logger::debug('prevented renaming asset because of missing permissions.');
                }

                $asset->setValues($updateData);
                $asset->save();

                return new UpdateAssetResult($this->elementService->getElementTreeNodeConfig($asset));
            }

            $msg = 'prevented moving asset, asset with same path+key already exists at target location or the asset is locked. ID: ' . $asset->getId();
            Logger::debug($msg);
            throw new BadRequestHttpException($msg);
        }

        if ($asset->isAllowed('rename') && isset($updateData['filename'])) {
            $asset->setFilename($updateData['filename']);
            $asset->save();

            return new UpdateAssetResult($this->elementService->getElementTreeNodeConfig($asset));
        }

        Logger::debug('prevented update asset because of missing permissions');
        throw new AccessDeniedHttpException('prevented update asset because of missing permissions');
    }
}
