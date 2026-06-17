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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper;

use Exception;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\MarkGridConfigFavourite\MarkGridConfigFavouritePayload;
use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Bundle\AdminBundle\Model\GridConfigFavourite;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class MarkGridConfigFavouriteHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(MarkGridConfigFavouritePayload $payload): MarkGridConfigFavouriteResult
    {
        $classId = $payload->classId;
        $gridConfigId = $payload->gridConfigId;
        $searchType = $payload->searchType;
        $type = $payload->type;
        $adminUser = $this->userContext->getAdminUser();
        $asset = Asset::getById(is_numeric($classId) ? (int) $classId : 0);

        if (!$asset || !$asset->isAllowed('list')) {
            throw new AccessDeniedHttpException();
        }

        $favourite = new GridConfigFavourite();
        $favourite->setOwnerId($adminUser->getId());
        $favourite->setClassId($classId);
        $favourite->setSearchType($searchType);
        $favourite->setType($type);

        try {
            if ($gridConfigId !== 0) {
                $gridConfig = GridConfig::getById($gridConfigId);
                $favourite->setGridConfigId($gridConfig->getId());
            }

            $favourite->setObjectId(0);
            $favourite->save();
        } catch (Exception) {
            $favourite->delete();
        }

        return new MarkGridConfigFavouriteResult(false);
    }
}
