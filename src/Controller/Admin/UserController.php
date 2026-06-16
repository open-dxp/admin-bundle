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

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\User\AddUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\DeleteUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\DeleteUserImageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\Disable2FaHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetMinimalUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetTokenLoginLinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUserImageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUserTreeChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUsersHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\Reset2FaSecretHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\SearchUsersHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\SendInvitationLinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UpdateUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UploadUserImageHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
        #[MapQueryParameter] int $node = 0,
    ): JsonResponse {
        return $this->adminJson($getUserTreeChildren($node));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/add', name: 'opendxp_admin_user_add', methods: ['POST'])]
    public function addAction(Request $request, AddUserHandler $addUser): JsonResponse
    {
        $referenceId = $request->request->has('rid') ? (int) $request->request->get('rid') : null;

        $result = $addUser(
            type: $request->request->get('type'),
            parentId: $request->request->getInt('parentId'),
            name: trim($request->request->get('name', '')),
            active: $request->request->getBoolean('active'),
            referenceId: $referenceId,
        );

        return $this->adminJson(ApiResponse::ok(['id' => $result->id]));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/delete', name: 'opendxp_admin_user_delete', methods: ['DELETE'])]
    public function deleteAction(Request $request, DeleteUserHandler $deleteUser): JsonResponse
    {
        $deleteUser($request->request->getInt('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/update', name: 'opendxp_admin_user_update', methods: ['PUT'])]
    public function updateAction(Request $request, UpdateUserHandler $updateUser): JsonResponse
    {
        $values = $request->request->has('data')
            ? $this->decodeJson($request->request->get('data'), true)
            : null;

        $workspaces = $request->request->has('workspaces')
            ? $this->decodeJson($request->request->get('workspaces'), true)
            : null;

        $keyBindingsJson = $request->request->has('keyBindings')
            ? $request->request->get('keyBindings')
            : null;

        $updateUser($request->request->getInt('id'), $values, $workspaces, $keyBindingsJson);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/get', name: 'opendxp_admin_user_get', methods: ['GET'])]
    public function getAction(
        GetUserHandler $getUser,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse {
        $result = $getUser($id);

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
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse {
        $result = $getMinimalUser($id);

        return $this->adminJson([
            'id' => $result->id,
            'admin' => $result->admin,
            'active' => $result->active,
            'permissionInfo' => $result->permissionInfo,
        ]);
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/upload-image', name: 'opendxp_admin_user_uploadimage', methods: ['POST'])]
    public function uploadImageAction(
        Request $request,
        UploadUserImageHandler $uploadUserImage,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse {
        $targetUserId = $request->query->has('id') ? $id : null;

        /** @var UploadedFile $avatarFile */
        $avatarFile = $request->files->get('Filedata');

        $uploadUserImage($targetUserId, $avatarFile);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response = $this->adminJson(ApiResponse::ok());
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/user/delete-image', name: 'opendxp_admin_user_deleteimage', methods: ['DELETE'])]
    public function deleteImageAction(
        Request $request,
        DeleteUserImageHandler $deleteUserImage,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse {
        $targetUserId = $request->query->has('id') ? $id : null;
        $deleteUserImage($targetUserId);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/user/disable-2fa', name: 'opendxp_admin_user_disable2fasecret', methods: ['DELETE'])]
    public function disable2FaSecretAction(Disable2FaHandler $disable2Fa): JsonResponse
    {
        try {
            $disable2Fa();
        } catch (\Throwable $e) {
            return $this->adminJson(ApiResponse::error($e->getMessage()));
        }

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/reset-2fa-secret', name: 'opendxp_admin_user_reset2fasecret', methods: ['PUT'])]
    public function reset2FaSecretAction(Request $request, Reset2FaSecretHandler $reset2FaSecret): JsonResponse
    {
        $reset2FaSecret($request->request->getInt('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/user/get-image', name: 'opendxp_admin_user_getimage', methods: ['GET'])]
    public function getImageAction(
        Request $request,
        GetUserImageHandler $getUserImage,
        #[MapQueryParameter] int $id = 0,
    ): StreamedResponse {
        $targetUserId = $request->query->has('id') ? $id : null;
        $stream = $getUserImage($targetUserId);

        return new StreamedResponse(function () use ($stream): void {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => 'image/png',
        ]);
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/get-token-login-link', name: 'opendxp_admin_user_gettokenloginlink', methods: ['GET'])]
    public function getTokenLoginLinkAction(
        GetTokenLoginLinkHandler $getTokenLoginLink,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse {
        try {
            $result = $getTokenLoginLink($id);
        } catch (\Throwable $e) {
            return $this->adminJson(ApiResponse::error($e->getMessage()));
        }

        return $this->adminJson(ApiResponse::ok(['link' => $result->link]));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/search', name: 'opendxp_admin_user_search', methods: ['GET'])]
    public function searchAction(
        SearchUsersHandler $searchUsers,
        #[MapQueryParameter] ?string $query = null,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['users' => $searchUsers($query)]));
    }

    #[IsGranted(CorePermission::ShareConfigurations->value)]
    #[Route('/user/get-users-for-sharing', name: 'opendxp_admin_user_getusersforsharing', methods: ['GET'])]
    public function getUsersForSharingAction(
        GetUsersHandler $getUsers,
        #[MapQueryParameter(name: 'include_current_user')] ?string $includeCurrentUser = null,
        #[MapQueryParameter] ?string $permission = null,
    ): JsonResponse {
        return $this->getUsersAction($getUsers, $includeCurrentUser, $permission);
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/get-users', name: 'opendxp_admin_user_getusers', methods: ['GET'])]
    public function getUsersAction(
        GetUsersHandler $getUsers,
        #[MapQueryParameter(name: 'include_current_user')] ?string $includeCurrentUser = null,
        #[MapQueryParameter] ?string $permission = null,
    ): JsonResponse {
        $users = $getUsers(
            includeCurrentUser: (bool) $includeCurrentUser,
            permission: $permission,
        );

        return $this->adminJson(ApiResponse::ok(['total' => count($users), 'data' => $users]));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/invitationlink', name: 'opendxp_admin_user_invitationlink', methods: ['POST'])]
    public function invitationLinkAction(
        Request $request,
        SendInvitationLinkHandler $sendInvitationLink,
        GeneralHostResolver $generalHostResolver,
    ): JsonResponse {
        $username = (string) $request->request->get('username', '');
        $domain = $generalHostResolver->resolve(['source' => $request]) ?? '';
        $result = $sendInvitationLink($username, $domain);

        return $this->adminJson(ApiResponse::fromBool($result->success, ['message' => $result->message]));
    }
}
