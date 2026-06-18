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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\CreateAssetFolder;

use OpenDxp\Bundle\AdminBundle\Handler\Asset\CreateAssetFolder\CreateAssetFolderPayload;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class CreateAssetFolderHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(CreateAssetFolderPayload $payload): void
    {
        $parentId = $payload->parentId;
        $name = $payload->name;
        $adminUser = $this->userContext->getAdminUser();
        $parentAsset = Asset::getById($parentId);

        if (!$parentAsset->isAllowed('create')) {
            throw new AccessDeniedHttpException('prevented creating asset because of missing permissions');
        }

        if (Asset::getByPath($parentAsset->getRealFullPath() . '/' . $name)) {
            throw new BadRequestHttpException('Asset with same path+key already exists');
        }

        Asset::create($parentId, [
            'filename' => $name,
            'type' => 'folder',
            'userOwner' => $adminUser->getId(),
            'userModification' => $adminUser->getId(),
        ]);
    }
}
