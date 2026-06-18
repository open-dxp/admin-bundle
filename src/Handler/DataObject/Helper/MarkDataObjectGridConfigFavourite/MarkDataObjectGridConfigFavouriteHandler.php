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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\MarkDataObjectGridConfigFavourite;

use Exception;
use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Bundle\AdminBundle\Model\GridConfigFavourite;
use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class MarkDataObjectGridConfigFavouriteHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(MarkDataObjectGridConfigFavouritePayload $payload): MarkDataObjectGridConfigFavouriteResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $object = DataObject::getById($payload->objectId);
        if (!$object) {
            throw new NotFoundHttpException();
        }

        if (!$object->isAllowed('list')) {
            throw new AccessDeniedHttpException();
        }

        $class = DataObject\ClassDefinition::getById($payload->classId);
        if (!$class) {
            throw new BadRequestHttpException('class ' . $payload->classId . ' does not exist anymore');
        }

        $favourite = new GridConfigFavourite();
        $favourite->setOwnerId($adminUser->getId());
        $favourite->setClassId($payload->classId);
        $favourite->setSearchType($payload->searchType);
        $favourite->setType($payload->type);

        $specializedConfigs = false;

        try {
            if ($payload->gridConfigId !== 0) {
                $gridConfig = GridConfig::getById($payload->gridConfigId);
                $favourite->setGridConfigId($gridConfig->getId());
            }

            $favourite->setObjectId($payload->objectId);
            $favourite->save();

            if ($payload->global) {
                $favourite->setObjectId(0);
                $favourite->save();
            }

            $count = Db::get()->fetchOne(
                'SELECT * FROM gridconfig_favourites WHERE ownerId = ? AND classId = ? AND searchType = ? AND objectId != ? AND objectId != 0 AND `type` != ?',
                [$adminUser->getId(), $payload->classId, $payload->searchType, $payload->objectId, $payload->type]
            );
            $specializedConfigs = $count > 0;
        } catch (Exception) {
            $favourite->delete();
        }

        return new MarkDataObjectGridConfigFavouriteResult($specializedConfigs);
    }
}
