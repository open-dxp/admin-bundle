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

final class GetRoleTreeChildrenHandler
{
    public function __invoke(int $parentId): array
    {
        $list = new User\Role\Listing();
        $list->setCondition('parentId = ?', $parentId);
        $list->load();

        $roles = [];
        foreach ($list->getItems() as $role) {
            $roles[] = $this->buildRoleTreeNodeConfig($role);
        }

        return $roles;
    }

    private function buildRoleTreeNodeConfig(User\Role|User\Role\Folder $role): array
    {
        $tmpUser = [
            'id' => $role->getId(),
            'text' => $role->getName(),
            'elementType' => 'role',
            'qtipCfg' => [
                'title' => 'ID: ' . $role->getId(),
            ],
        ];

        if ($role instanceof User\Role\Folder) {
            $tmpUser['leaf'] = false;
            $tmpUser['iconCls'] = 'opendxp_icon_folder';
            $tmpUser['expanded'] = true;
            $tmpUser['allowChildren'] = true;

            if ($role->hasChildren()) {
                $tmpUser['expanded'] = false;
            } else {
                $tmpUser['loaded'] = true;
            }
        } else {
            $tmpUser['leaf'] = true;
            $tmpUser['iconCls'] = 'opendxp_icon_roles';
            $tmpUser['allowChildren'] = false;
        }

        return $tmpUser;
    }
}
