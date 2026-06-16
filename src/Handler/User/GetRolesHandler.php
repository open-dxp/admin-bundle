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

namespace OpenDxp\Bundle\AdminBundle\Handler\User;

use OpenDxp\Model\User;

final class GetRolesHandler
{
    public function __invoke(?string $permission): array
    {
        $list = new User\Role\Listing();
        $list->setCondition('`type` = "role"');
        $list->load();

        $roles = [];
        foreach ($list->getRoles() as $role) {
            if ($permission === null || in_array($permission, $role->getPermissions())) {
                $roles[] = [
                    'id' => $role->getId(),
                    'label' => $role->getName(),
                ];
            }
        }

        return $roles;
    }
}
