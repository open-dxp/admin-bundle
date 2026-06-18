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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\DeleteAsset;

use OpenDxp\Bundle\AdminBundle\Handler\Asset\DeleteAsset\DeleteAssetPayload;
use OpenDxp\Db\Helper;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class DeleteAssetHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(DeleteAssetPayload $payload): DeleteAssetResult
    {
        $type = $payload->type;
        $id = $payload->id;
        $amount = $payload->amount;
        $adminUser = $this->userContext->getAdminUser();
        if ($type === 'children') {
            $parentAsset = Asset::getById($id);

            $list = new Asset\Listing();
            $list->setCondition('`path` LIKE ?', [Helper::escapeLike($parentAsset->getRealFullPath()) . '/%']);
            $list->setLimit($amount);
            $list->setOrderKey('LENGTH(`path`)', false);
            $list->setOrder('DESC');

            $deleted = [];
            foreach ($list as $asset) {
                $deleted[$asset->getId()] = $asset->getRealFullPath();
                if ($asset->isAllowed('delete') && !$asset->isLocked()) {
                    $asset->delete();
                }
            }

            return new DeleteAssetResult($deleted);
        }

        $asset = Asset::getById($id);
        if ($asset && $asset->isAllowed('delete')) {
            if ($asset->isLocked()) {
                throw new BadRequestHttpException('prevented deleting asset, because it is locked: ID: ' . $asset->getId());
            }

            $asset->delete();

            return new DeleteAssetResult();
        }

        throw new AccessDeniedHttpException();
    }
}
