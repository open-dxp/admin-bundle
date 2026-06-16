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

use Exception;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DeleteUserHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    /**
     * @throws Exception
     */
    public function __invoke(int $id): void
    {
        $adminUser = $this->userContext->getAdminUser();
        $currentUserId = (int) $adminUser?->getId();

        $user = User\AbstractUser::getById($id);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        if (($user instanceof User\Folder && !$adminUser?->isAdmin())
            || ($user instanceof User && $user->isAdmin() && !$adminUser?->isAdmin())
        ) {
            throw new AccessDeniedHttpException('You are not allowed to delete this user');
        }

        if ($user instanceof User\Role\Folder) {
            $list = [$user];
            $this->populateChildNodes($user, $list, true, $currentUserId);
            $listCount = count($list);
            for ($i = $listCount - 1; $i >= 0; $i--) {
                $list[$i]->delete();
            }
        } elseif ($user->getId()) {
            $user->delete();
        }
    }

    /**
     * @throws Exception
     */
    private function populateChildNodes(User\AbstractUser $node, array &$currentList, bool $roleMode, int $currentUserId): void
    {
        $list = $roleMode ? new User\Role\Listing() : new User\Listing();
        $list->setCondition('parentId = ?', $node->getId());
        $list->setOrder('ASC');
        $list->setOrderKey('name');
        $list->load();

        $childList = $roleMode ? $list->getRoles() : $list->getUsers();

        foreach ($childList as $child) {
            if ($child->getId() === $currentUserId) {
                throw new Exception('Cannot delete current user');
            }
            if ($child->getId() && $currentUserId && $child->getName() !== 'system') {
                $currentList[] = $child;
                $this->populateChildNodes($child, $currentList, $roleMode, $currentUserId);
            }
        }
    }
}
