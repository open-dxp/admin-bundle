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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\GetUser;

use OpenDxp\Bundle\AdminBundle\Perspective\Config;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element;
use OpenDxp\Model\User;
use OpenDxp\Model\User\Workspace;
use OpenDxp\Tool;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetUserHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(GetUserPayload $payload): GetUserResult
    {
        if ($payload->id < 1) {
            throw new NotFoundHttpException('User not found');
        }

        $user = User::getById($payload->id);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $adminUser = $this->userContext->getAdminUser();
        if ($user->isAdmin() && !$adminUser?->isAdmin()) {
            throw new AccessDeniedHttpException('Only admin users are allowed to modify admin users');
        }
        // workspaces
        $types = ['asset', 'document', 'object'];
        foreach ($types as $type) {
            /** @var Workspace\Document[]|Workspace\Asset[]|Workspace\DataObject[] $workspaces */
            $workspaces = $user->{'getWorkspaces' . ucfirst($type)}();
            foreach ($workspaces as $wKey => $workspace) {
                $el = Element\Service::getElementById($type, $workspace->getCid());
                if ($el) {
                    $workspaceVars = $workspace->getObjectVars();
                    $workspaceVars['path'] = $el->getRealFullPath();
                    $workspaces[$wKey] = $workspaceVars;
                }
            }
            $user->{'setWorkspaces' . ucfirst($type)}($workspaces);
        }

        // object <=> user dependencies
        $userObjects = DataObject\Service::getObjectsReferencingUser((int) $user->getId());
        $userObjectData = [];
        $hasHidden = false;

        foreach ($userObjects as $o) {
            if ($o->isAllowed('list')) {
                $userObjectData[] = [
                    'path' => $o->getRealFullPath(),
                    'id' => $o->getId(),
                    'subtype' => $o->getClass()->getName(),
                ];
            } else {
                $hasHidden = true;
            }
        }

        // get available permissions
        $availableUserPermissionsList = new User\Permission\Definition\Listing();
        $availableUserPermissionsList->setOrderKey('category');
        $availableUserPermissions = $availableUserPermissionsList->load();

        $availableUserPermissionsData = [];
        foreach ($availableUserPermissions as $availableUserPermission) {
            $availableUserPermissionsData[] = $availableUserPermission->getObjectVars();
        }

        // get available roles
        $list = new User\Role\Listing();
        $list->setCondition('`type` = ?', ['role']);
        $list->load();

        $roles = [];
        foreach ($list->getItems() as $role) {
            $roles[] = [$role->getId(), $role->getName()];
        }

        // unset confidential information
        $userData = $user->getObjectVars();
        $userData['roles'] = $user->getRoles();
        $userData['docTypes'] = $user->getDocTypes();
        $contentLanguages = Tool\Admin::reorderWebsiteLanguages($user, Tool::getValidLanguages());
        $userData['contentLanguages'] = $contentLanguages;
        $userData['twoFactorAuthentication']['isActive'] = ($user->getTwoFactorAuthentication('enabled') || $user->getTwoFactorAuthentication('secret'));
        unset($userData['password'], $userData['passwordRecoveryToken'], $userData['twoFactorAuthentication']['secret']);
        $userData['hasImage'] = $user->hasImage();

        $availablePerspectives = Config::getAvailablePerspectives(null);

        return new GetUserResult(
            user: $userData,
            roles: $roles,
            permissions: $user->generatePermissionList(),
            availablePermissions: $availableUserPermissionsData,
            availablePerspectives: $availablePerspectives,
            validLanguages: Tool::getValidLanguages(),
            validLocales: Tool::getSupportedJSLocales(),
            objectDependencies: [
                'hasHidden' => $hasHidden,
                'dependencies' => $userObjectData,
            ],
        );
    }
}
