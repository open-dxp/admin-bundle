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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper;

use Exception;
use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Bundle\AdminBundle\Model\GridConfigFavourite;
use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class MarkDataObjectGridConfigFavouriteHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(
        int $objectId,
        ?string $classId,
        int $gridConfigId,
        ?string $searchType,
        bool $global,
        ?string $type,
    ): MarkDataObjectGridConfigFavouriteResult {
        $adminUser = $this->userContext->getAdminUser();
        $object = DataObject::getById($objectId);
        if (!$object) {
            throw new NotFoundHttpException();
        }

        if (!$object->isAllowed('list')) {
            throw new AccessDeniedHttpException();
        }

        $class = DataObject\ClassDefinition::getById($classId);
        if (!$class) {
            throw new BadRequestHttpException('class ' . $classId . ' does not exist anymore');
        }

        $favourite = new GridConfigFavourite();
        $favourite->setOwnerId($adminUser->getId());
        $favourite->setClassId($classId);
        $favourite->setSearchType($searchType);
        $favourite->setType($type);

        $specializedConfigs = false;

        try {
            if ($gridConfigId !== 0) {
                $gridConfig = GridConfig::getById($gridConfigId);
                $favourite->setGridConfigId($gridConfig->getId());
            }

            $favourite->setObjectId($objectId);
            $favourite->save();

            if ($global) {
                $favourite->setObjectId(0);
                $favourite->save();
            }

            $count = Db::get()->fetchOne(
                'SELECT * FROM gridconfig_favourites WHERE ownerId = ? AND classId = ? AND searchType = ? AND objectId != ? AND objectId != 0 AND `type` != ?',
                [$adminUser->getId(), $classId, $searchType, $objectId, $type]
            );
            $specializedConfigs = $count > 0;
        } catch (Exception) {
            $favourite->delete();
        }

        return new MarkDataObjectGridConfigFavouriteResult($specializedConfigs);
    }
}
