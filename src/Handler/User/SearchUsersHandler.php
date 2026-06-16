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

final class SearchUsersHandler
{
    public function __invoke(?string $query): array
    {
        $q = '%' . $query . '%';

        $list = new User\Listing();
        $list->setCondition('name LIKE ? OR firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR id = ?', [$q, $q, $q, $q, $query]);
        $list->setOrder('ASC');
        $list->setOrderKey('name');

        $users = [];
        foreach ($list->getUsers() as $user) {
            if ($user->getId() && $user->getName() !== 'system') {
                $users[] = [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                ];
            }
        }

        return $users;
    }
}
