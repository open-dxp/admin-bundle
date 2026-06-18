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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\User;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetRole\GetRoleHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetRole\GetRolePayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetRoles\GetRolesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetRoles\GetRolesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetRoleTreeChildren\GetRoleTreeChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetRoleTreeChildren\GetRoleTreeChildrenPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
class RoleController extends AdminAbstractController
{
    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/role-tree-get-children-by-id', name: 'opendxp_admin_user_roletreegetchildrenbyid', methods: ['GET'])]
    public function roleTreeGetChildrenByIdAction(
        GetRoleTreeChildrenPayload $payload,
        GetRoleTreeChildrenHandler $getRoleTreeChildren,
    ): JsonResponse {
        return $this->adminJson($getRoleTreeChildren($payload));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/role-get', name: 'opendxp_admin_user_roleget', methods: ['GET'])]
    public function roleGetAction(
        GetRolePayload $payload,
        GetRoleHandler $getRole,
    ): JsonResponse {
        $result = $getRole($payload);

        return $this->adminJson(ApiResponse::ok([
            'role' => $result->role,
            'permissions' => $result->permissions,
            'classes' => $result->classes,
            'docTypes' => $result->docTypes,
            'availablePermissions' => $result->availablePermissions,
            'availablePerspectives' => $result->availablePerspectives,
            'validLanguages' => $result->validLanguages,
        ]));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/get-roles', name: 'opendxp_admin_user_getroles', methods: ['GET'])]
    public function getRolesAction(
        GetRolesPayload $payload,
        GetRolesHandler $getRoles,
    ): JsonResponse {
        $roles = $getRoles($payload);

        return $this->adminJson(ApiResponse::ok(['total' => count($roles), 'data' => $roles]));
    }

    #[IsGranted(CorePermission::ShareConfigurations->value)]
    #[Route('/user/get-roles-for-sharing', name: 'opendxp_admin_user_getrolesforsharing', methods: ['GET'])]
    public function getRolesForSharingAction(
        GetRolesPayload $payload,
        GetRolesHandler $getRoles,
    ): JsonResponse {
        $roles = $getRoles($payload);

        return $this->adminJson(ApiResponse::ok(['total' => count($roles), 'data' => $roles]));
    }
}
