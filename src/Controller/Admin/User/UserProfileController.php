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

use OpenDxp\Bundle\AdminBundle\Attribute\AsHtmlContentTypeResponse;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetCurrentUser\GetCurrentUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetCurrentUser\GetCurrentUserPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\ResetMy2FaSecret\ResetMy2FaSecretHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UpdateCurrentUser\UpdateCurrentUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UpdateCurrentUser\UpdateCurrentUserPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\UploadUserImage\UploadUserImageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UploadUserImage\UploadUserImagePayload;
use OpenDxp\Bundle\AdminBundle\Helper\User as UserHelper;
use OpenDxp\Bundle\AdminBundle\Payload\Common\EmptyPayload;
use OpenDxp\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
class UserProfileController extends AdminAbstractController
{
    #[AsHtmlContentTypeResponse]
    #[Route('/user/upload-current-user-image', name: 'opendxp_admin_user_uploadcurrentuserimage', methods: ['POST'])]
    public function uploadCurrentUserImageAction(
        UploadUserImagePayload $payload,
        UploadUserImageHandler $handler,
    ): JsonResponse {
        $user = $this->getAdminUser();

        // this endpoint is for the caller's own avatar only; unlike upload-image, this must hold even for admins
        if ($user === null || $payload->targetUserId !== $user->getId()) {
            Logger::warn('prevented save current user, because ids do not match. ');

            return $this->adminJson(ApiResponse::error());
        }

        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/user/update-current-user', name: 'opendxp_admin_user_updatecurrentuser', methods: ['PUT'])]
    public function updateCurrentUserAction(
        Request $request,
        UpdateCurrentUserPayload $payload,
        UpdateCurrentUserHandler $handler,
    ): JsonResponse {
        if (!$request->request->has('id')) {
            return $this->adminJson(false);
        }

        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/user/get-current-user', name: 'opendxp_admin_user_getcurrentuser', methods: ['GET'])]
    public function getCurrentUserAction(
        GetCurrentUserPayload $payload,
        GetCurrentUserHandler $handler,
    ): Response {
        $result = $handler($payload);

        $response = new Response('opendxp.currentuser = ' . $this->encodeJson($result->userData));
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    #[Route('/user/reset-my-2fa-secret', name: 'opendxp_admin_user_reset_my_2fa_secret', methods: ['PUT'])]
    public function resetMy2FaSecretAction(
        EmptyPayload $payload,
        ResetMy2FaSecretHandler $handler,
    ): JsonResponse {

        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/user/get-default-key-bindings', name: 'opendxp_admin_user_getdefaultkeybindings', methods: ['GET'])]
    public function getDefaultKeyBindingsAction(): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok(['data' => UserHelper::getDefaultKeyBindings()]));
    }
}
