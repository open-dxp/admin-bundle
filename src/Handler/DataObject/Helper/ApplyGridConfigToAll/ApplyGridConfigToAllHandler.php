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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ApplyGridConfigToAll;

use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;

final class ApplyGridConfigToAllHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(ApplyGridConfigToAllPayload $payload): void
    {
        $adminUser = $this->userContext->getAdminUser();
        $object = DataObject::getById($payload->objectId);
        if (!$object) {
            throw new NotFoundHttpException();
        }

        if (!$object->isAllowed('list')) {
            throw new AccessDeniedHttpException();
        }

        Db::get()->executeStatement(
            'DELETE FROM gridconfig_favourites WHERE ownerId = ? AND classId = ? AND searchType = ? AND objectId != ? AND objectId != 0',
            [$adminUser->getId(), $payload->classId, $payload->searchType, $payload->objectId]
        );
    }
}
