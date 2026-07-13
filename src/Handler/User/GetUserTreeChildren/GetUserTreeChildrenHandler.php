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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\GetUserTreeChildren;

use OpenDxp\Model\User;

final class GetUserTreeChildrenHandler
{
    public function __invoke(GetUserTreeChildrenPayload $payload): GetUserTreeChildrenResult
    {
        $list = new User\Listing();
        $list->setCondition('parentId = ?', $payload->node);
        $list->setOrder('ASC');
        $list->setOrderKey('name');
        $list->load();

        $users = [];
        foreach ($list->getUsers() as $user) {
            if ($user->getId() && $user->getName() !== 'system') {
                $users[] = $this->buildTreeNodeConfig($user);
            }
        }

        return new GetUserTreeChildrenResult(users: $users);
    }

    private function buildTreeNodeConfig(User|User\Folder $user): array
    {
        $tmpUser = [
            'id' => $user->getId(),
            'text' => $user->getName(),
            'elementType' => 'user',
            'type' => $user->getType(),
            'qtipCfg' => [
                'title' => 'ID: ' . $user->getId(),
            ],
        ];

        if ($user instanceof User\Folder) {
            $tmpUser['leaf'] = false;
            $tmpUser['iconCls'] = 'opendxp_icon_folder';
            $tmpUser['expanded'] = true;
            $tmpUser['allowChildren'] = true;

            if ($user->hasChildren()) {
                $tmpUser['expanded'] = false;
            } else {
                $tmpUser['loaded'] = true;
            }
        } else {
            $tmpUser['leaf'] = true;
            $tmpUser['iconCls'] = 'opendxp_icon_user';
            if (!$user->getActive()) {
                $tmpUser['cls'] = ' opendxp_unpublished';
            }
            $tmpUser['allowChildren'] = false;
            $tmpUser['admin'] = $user->isAdmin();
        }

        return $tmpUser;
    }
}
