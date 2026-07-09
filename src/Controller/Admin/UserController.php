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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Attribute\AsHtmlContentTypeResponse;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\User\AddUser\AddUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\AddUser\AddUserPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\DeleteUser\DeleteUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\DeleteUser\DeleteUserPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\DeleteUserImage\DeleteUserImageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\DeleteUserImage\DeleteUserImagePayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\Disable2Fa\Disable2FaHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\Disable2Fa\Disable2FaPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetMinimalUser\GetMinimalUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetMinimalUser\GetMinimalUserPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetTokenLoginLink\GetTokenLoginLinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetTokenLoginLink\GetTokenLoginLinkPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUser\GetUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUser\GetUserPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUserImage\GetUserImageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUserImage\GetUserImagePayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUserTreeChildren\GetUserTreeChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUserTreeChildren\GetUserTreeChildrenPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUsers\GetUsersHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUsers\GetUsersPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\Reset2FaSecret\Reset2FaSecretHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\Reset2FaSecret\Reset2FaSecretPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\SearchUsers\SearchUsersHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\SearchUsers\SearchUsersPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\SendInvitationLink\SendInvitationLinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\SendInvitationLink\SendInvitationLinkPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\UpdateUser\UpdateUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UpdateUser\UpdateUserPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\UploadUserImage\UploadUserImageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UploadUserImage\UploadUserImagePayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
class UserController extends AdminAbstractController
{
    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/tree-get-children-by-id', name: 'opendxp_admin_user_treegetchildrenbyid', methods: ['GET'])]
    public function treeGetChildrenByIdAction(
        GetUserTreeChildrenHandler $getUserTreeChildren,
        GetUserTreeChildrenPayload $payload,
    ): JsonResponse {
        return $this->adminJson($getUserTreeChildren($payload));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/add', name: 'opendxp_admin_user_add', methods: ['POST'])]
    public function addAction(
        AddUserHandler $addUser,
        AddUserPayload $payload,
    ): JsonResponse {
        $result = $addUser($payload);

        return $this->adminJson(ApiResponse::ok(['id' => $result->id]));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/delete', name: 'opendxp_admin_user_delete', methods: ['DELETE'])]
    public function deleteAction(
        DeleteUserHandler $deleteUser,
        DeleteUserPayload $payload,
    ): JsonResponse {
        $deleteUser($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/update', name: 'opendxp_admin_user_update', methods: ['PUT'])]
    public function updateAction(
        UpdateUserHandler $updateUser,
        UpdateUserPayload $payload,
    ): JsonResponse {
        $updateUser($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/get', name: 'opendxp_admin_user_get', methods: ['GET'])]
    public function getAction(
        GetUserHandler $getUser,
        GetUserPayload $payload,
    ): JsonResponse {
        $result = $getUser($payload);

        return $this->adminJson(ApiResponse::ok([
            'user' => $result->userData,
            'roles' => $result->roles,
            'permissions' => $result->permissions,
            'availablePermissions' => $result->availablePermissions,
            'availablePerspectives' => $result->availablePerspectives,
            'validLanguages' => $result->validLanguages,
            'validLocales' => $result->validLocales,
            'objectDependencies' => $result->objectDependencies,
        ]));
    }

    #[Route('/user/get-minimal', name: 'opendxp_admin_user_getminimal', methods: ['GET'])]
    public function getMinimalAction(
        GetMinimalUserHandler $getMinimalUser,
        GetMinimalUserPayload $payload,
    ): JsonResponse {
        $result = $getMinimalUser($payload);

        return $this->adminJson([
            'id' => $result->id,
            'admin' => $result->admin,
            'active' => $result->active,
            'permissionInfo' => $result->permissionInfo,
        ]);
    }

    #[AsHtmlContentTypeResponse]
    #[Route('/user/upload-image', name: 'opendxp_admin_user_uploadimage', methods: ['POST'])]
    public function uploadImageAction(
        UploadUserImageHandler $uploadUserImage,
        UploadUserImagePayload $payload,
    ): JsonResponse {
        $uploadUserImage($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/user/delete-image', name: 'opendxp_admin_user_deleteimage', methods: ['DELETE'])]
    public function deleteImageAction(
        DeleteUserImageHandler $deleteUserImage,
        DeleteUserImagePayload $payload,
    ): JsonResponse {
        $deleteUserImage($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/user/disable-2fa', name: 'opendxp_admin_user_disable2fasecret', methods: ['DELETE'])]
    public function disable2FaSecretAction(
        Disable2FaHandler $disable2Fa,
        Disable2FaPayload $payload,
    ): JsonResponse {
        try {
            $disable2Fa($payload);
        } catch (\Throwable $e) {
            return $this->adminJson(ApiResponse::error($e->getMessage()));
        }

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/reset-2fa-secret', name: 'opendxp_admin_user_reset2fasecret', methods: ['PUT'])]
    public function reset2FaSecretAction(
        Reset2FaSecretHandler $reset2FaSecret,
        Reset2FaSecretPayload $payload,
    ): JsonResponse {
        $reset2FaSecret($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/user/get-image', name: 'opendxp_admin_user_getimage', methods: ['GET'])]
    public function getImageAction(
        GetUserImageHandler $handler,
        GetUserImagePayload $payload,
    ): StreamedResponse {
        $result = $handler($payload);

        return new StreamedResponse(function () use ($result): void {
            fpassthru($result->image);
        }, 200, [
            'Content-Type' => 'image/png',
        ]);
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/get-token-login-link', name: 'opendxp_admin_user_gettokenloginlink', methods: ['GET'])]
    public function getTokenLoginLinkAction(
        GetTokenLoginLinkHandler $getTokenLoginLink,
        GetTokenLoginLinkPayload $payload,
    ): JsonResponse {
        try {
            $result = $getTokenLoginLink($payload);
        } catch (\Throwable $e) {
            return $this->adminJson(ApiResponse::error($e->getMessage()));
        }

        return $this->adminJson(ApiResponse::ok(['link' => $result->link]));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/search', name: 'opendxp_admin_user_search', methods: ['GET'])]
    public function searchAction(
        SearchUsersHandler $searchUsers,
        SearchUsersPayload $payload,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['users' => $searchUsers($payload)]));
    }

    #[IsGranted(CorePermission::ShareConfigurations->value)]
    #[Route('/user/get-users-for-sharing', name: 'opendxp_admin_user_getusersforsharing', methods: ['GET'])]
    public function getUsersForSharingAction(
        GetUsersHandler $getUsers,
        GetUsersPayload $payload,
    ): JsonResponse {
        return $this->getUsersAction($getUsers, $payload);
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/get-users', name: 'opendxp_admin_user_getusers', methods: ['GET'])]
    public function getUsersAction(
        GetUsersHandler $getUsers,
        GetUsersPayload $payload,
    ): JsonResponse {
        $users = $getUsers($payload);

        return $this->adminJson(ApiResponse::ok(['total' => count($users), 'data' => $users]));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/invitationlink', name: 'opendxp_admin_user_invitationlink', methods: ['POST'])]
    public function invitationLinkAction(
        SendInvitationLinkHandler $sendInvitationLink,
        SendInvitationLinkPayload $payload,
    ): JsonResponse {
        $result = $sendInvitationLink($payload);

        return $this->adminJson(ApiResponse::fromBool($result->success, ['message' => $result->message]));
    }
}
