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
use OpenDxp\Bundle\AdminBundle\Attribute\SessionGatewayAware;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetCurrentUser\GetCurrentUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetCurrentUser\GetCurrentUserPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetDefaultKeyBindings\GetDefaultKeyBindingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\ResetMy2FaSecret\ResetMy2FaSecretHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UpdateCurrentUser\UpdateCurrentUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UpdateCurrentUser\UpdateCurrentUserPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\UploadUserImage\UploadUserImageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UploadUserImage\UploadUserImagePayload;
use OpenDxp\Bundle\AdminBundle\Security\Voter\OwnUserVoter;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\PasswordResetSessionGateway;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
class UserProfileController extends AdminAbstractController
{
    #[AsHtmlContentTypeResponse]
    #[IsGranted(OwnUserVoter::OWN_USER, subject: 'payload')]
    #[Route('/user/upload-current-user-image', name: 'opendxp_admin_user_uploadcurrentuserimage', methods: ['POST'])]
    public function uploadCurrentUserImageAction(
        UploadUserImagePayload $payload,
        UploadUserImageHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(OwnUserVoter::OWN_USER, subject: 'payload')]
    #[SessionGatewayAware(PasswordResetSessionGateway::class)]
    #[Route('/user/update-current-user', name: 'opendxp_admin_user_updatecurrentuser', methods: ['PUT'])]
    public function updateCurrentUserAction(
        UpdateCurrentUserPayload $payload,
        UpdateCurrentUserHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[SessionGatewayAware(PasswordResetSessionGateway::class)]
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
        ResetMy2FaSecretHandler $handler,
    ): JsonResponse {

        $handler();

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Users->value)]
    #[Route('/user/get-default-key-bindings', name: 'opendxp_admin_user_getdefaultkeybindings', methods: ['GET'])]
    public function getDefaultKeyBindingsAction(GetDefaultKeyBindingsHandler $handler): JsonResponse
    {
        return $this->apiJson($handler());
    }
}
