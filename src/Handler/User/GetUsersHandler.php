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

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\User;

final class GetUsersHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(bool $includeCurrentUser, ?string $permission): array
    {
        $currentUserId = (int) $this->userContext->getAdminUser()?->getId();
        $list = new User\Listing();

        $conditions = ['type = "user"'];

        if (!$includeCurrentUser) {
            $conditions[] = 'id != ' . $currentUserId;
        }

        $list->setCondition(implode(' AND ', $conditions));
        $list->load();

        $users = [];
        foreach ($list->getUsers() as $user) {
            if (!$permission || $user->isAllowed($permission)) {
                $users[] = [
                    'id' => $user->getId(),
                    'label' => $user->getUsername(),
                ];
            }
        }

        return $users;
    }
}
