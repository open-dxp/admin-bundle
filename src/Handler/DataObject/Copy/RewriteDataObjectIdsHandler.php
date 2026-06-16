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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy;

use OpenDxp\Bundle\AdminBundle\Exception\DataObject\DataObjectNotFoundException;
use OpenDxp\Model\DataObject;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class RewriteDataObjectIdsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(int $objectId, array $idMapping): void
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $object = DataObject::getById($objectId) ?? throw new DataObjectNotFoundException($objectId);

        $object = DataObject\Service::rewriteIds($object, ['object' => $idMapping]);
        $object->setUserModification($userId);
        $object->save();
    }
}
