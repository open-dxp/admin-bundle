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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\GetModifiedObjects;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\DataObject;

final class GetModifiedObjectsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(): GetModifiedObjectsResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $list = DataObject::getList([
            'limit' => 10,
            'order' => 'DESC',
            'orderKey' => 'modificationDate',
            'condition' => 'userModification = ' . $userId,
        ]);

        $objects = [];
        foreach ($list as $object) {
            if ($object->isAllowed('view')) {
                $objects[] = [
                    'id' => $object->getId(),
                    'type' => $object->getType(),
                    'path' => $object->getRealFullPath(),
                    'date' => $object->getModificationDate(),
                ];
            }
        }

        return new GetModifiedObjectsResult(objects: $objects);
    }
}
