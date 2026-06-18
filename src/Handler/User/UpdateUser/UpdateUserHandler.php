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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\UpdateUser;

use Exception;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Element;
use OpenDxp\Model\User;
use OpenDxp\Tool;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UpdateUserHandler
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(UpdateUserPayload $payload): void
    {
        $adminUser = $this->userContext->getAdminUser();
        $currentUserIsAdmin = $adminUser?->isAdmin() ?? false;

        /** @var User\UserRole|null $user */
        $user = User\UserRole::getById($payload->id);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        if ($user instanceof User && $user->isAdmin() && !$currentUserIsAdmin) {
            throw new AccessDeniedHttpException('Only admin users are allowed to modify admin users');
        }
        if ($payload->values !== null) {
            $values = $payload->values;
            if (!empty($values['password'])) {
                if (strlen($values['password']) < 10) {
                    throw new Exception('Passwords have to be at least 10 characters long');
                }
                $values['password'] = Tool\Authentication::getPasswordHash($user->getName(), $values['password']);
            }

            // check if there are permissions transmitted, if so reset them all to false (they will be set later)
            foreach ($values as $key => $value) {
                if (str_starts_with($key, 'permission_')) {
                    $user->setAllAclToFalse();

                    break;
                }
            }

            if ($user instanceof User && isset($values['2fa_required'])) {
                $user->setTwoFactorAuthentication('required', (bool) $values['2fa_required']);
            }

            $user->setValues($values);

            // only admins are allowed to create admin users
            if ($user instanceof User && !$currentUserIsAdmin) {
                $user->setAdmin(false);
            }

            // check for permissions
            $availableUserPermissionsList = new User\Permission\Definition\Listing();
            $availableUserPermissions = $availableUserPermissionsList->load();

            foreach ($availableUserPermissions as $permission) {
                if (isset($values['permission_' . $permission->getKey()])) {
                    $user->setPermission($permission->getKey(), (bool) $values['permission_' . $permission->getKey()]);
                }
            }

            // check for workspaces
            if ($payload->workspaces !== null) {
                $processedPaths = ['object' => [], 'asset' => [], 'document' => []];
                foreach ($payload->workspaces as $type => $spaces) {
                    $newWorkspaces = [];
                    foreach ($spaces as $space) {
                        if (in_array($space['path'], $processedPaths[$type])) {
                            throw new Exception('Error saving workspaces as multiple entries found for path "' . $space['path'] . '" in ' . $this->translator->trans((string) $type, [], 'admin') . 's');
                        }

                        $element = Element\Service::getElementByPath($type, $space['path']);
                        if ($element) {
                            $className = '\\OpenDxp\\Model\\User\\Workspace\\' . Element\Service::getBaseClassNameForElement($type);
                            $workspace = new $className();
                            $workspace->setValues($space);

                            $workspace->setCid($element->getId());
                            $workspace->setCpath($element->getRealFullPath());
                            $workspace->setUserId($user->getId());

                            $newWorkspaces[] = $workspace;
                            $processedPaths[$type][] = $space['path'];
                        }
                    }
                    $user->{'setWorkspaces' . ucfirst($type)}($newWorkspaces);
                }
            }
        }

        if ($user instanceof User && $payload->keyBindingsJson !== null) {
            $keyBindings = json_decode($payload->keyBindingsJson, true);
            $tmpArray = [];
            foreach ($keyBindings as $item) {
                $tmpArray[] = json_decode($item, true);
            }
            $tmpArray = array_values(array_filter($tmpArray));
            $tmpArray = json_encode($tmpArray);

            $user->setKeyBindings($tmpArray);
        }

        $user->save();
    }
}
