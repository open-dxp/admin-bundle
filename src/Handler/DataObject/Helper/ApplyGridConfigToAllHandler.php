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

use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class ApplyGridConfigToAllHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(int $objectId, string $classId, string $searchType): void
    {
        $adminUser = $this->userContext->getAdminUser();
        $object = DataObject::getById($objectId);
        if (!$object) {
            throw new NotFoundHttpException();
        }

        if (!$object->isAllowed('list')) {
            throw new AccessDeniedHttpException();
        }

        Db::get()->executeStatement(
            'DELETE FROM gridconfig_favourites WHERE ownerId = ? AND classId = ? AND searchType = ? AND objectId != ? AND objectId != 0',
            [$adminUser->getId(), $classId, $searchType, $objectId]
        );
    }
}
