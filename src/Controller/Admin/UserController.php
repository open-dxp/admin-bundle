<?php


declare(strict_types=1);

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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Attribute\AsHtmlContentTypeResponse;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
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
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUsers\GetUsersHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUsers\GetUsersPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUserTreeChildren\GetUserTreeChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetUserTreeChildren\GetUserTreeChildrenPayload;
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
use OpenDxp\Security\CorePermission;
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
        GetUserTreeChildrenHandler $handler,
        GetUserTreeChildrenPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'users');
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/add', name: 'opendxp_admin_user_add', methods: ['POST'])]
    public function addAction(
        AddUserHandler $handler,
        AddUserPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/delete', name: 'opendxp_admin_user_delete', methods: ['DELETE'])]
    public function deleteAction(
        DeleteUserHandler $handler,
        DeleteUserPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/update', name: 'opendxp_admin_user_update', methods: ['PUT'])]
    public function updateAction(
        UpdateUserHandler $handler,
        UpdateUserPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/get', name: 'opendxp_admin_user_get', methods: ['GET'])]
    public function getAction(
        GetUserHandler $handler,
        GetUserPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/user/get-minimal', name: 'opendxp_admin_user_getminimal', methods: ['GET'])]
    public function getMinimalAction(
        GetMinimalUserHandler $handler,
        GetMinimalUserPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[AsHtmlContentTypeResponse]
    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/upload-image', name: 'opendxp_admin_user_uploadimage', methods: ['POST'])]
    public function uploadImageAction(
        UploadUserImageHandler $handler,
        UploadUserImagePayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/user/delete-image', name: 'opendxp_admin_user_deleteimage', methods: ['DELETE'])]
    public function deleteImageAction(
        DeleteUserImageHandler $handler,
        DeleteUserImagePayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/user/disable-2fa', name: 'opendxp_admin_user_disable2fasecret', methods: ['DELETE'])]
    public function disable2FaSecretAction(
        Disable2FaHandler $handler,
        Disable2FaPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/reset-2fa-secret', name: 'opendxp_admin_user_reset2fasecret', methods: ['PUT'])]
    public function reset2FaSecretAction(
        Reset2FaSecretHandler $handler,
        Reset2FaSecretPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
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
        GetTokenLoginLinkHandler $handler,
        GetTokenLoginLinkPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/search', name: 'opendxp_admin_user_search', methods: ['GET'])]
    public function searchAction(
        SearchUsersHandler $handler,
        SearchUsersPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::ShareConfigurations->value)]
    #[Route('/user/get-users-for-sharing', name: 'opendxp_admin_user_getusersforsharing', methods: ['GET'])]
    public function getUsersForSharingAction(
        GetUsersHandler $handler,
        GetUsersPayload $payload,
    ): JsonResponse {
        return $this->getUsersAction($handler, $payload);
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/get-users', name: 'opendxp_admin_user_getusers', methods: ['GET'])]
    public function getUsersAction(
        GetUsersHandler $handler,
        GetUsersPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/invitationlink', name: 'opendxp_admin_user_invitationlink', methods: ['POST'])]
    public function invitationLinkAction(
        SendInvitationLinkHandler $handler,
        SendInvitationLinkPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }
}
