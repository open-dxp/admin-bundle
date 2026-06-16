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
use OpenDxp\Bundle\AdminBundle\Handler\User\GetCurrentUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\ResetMy2FaSecretHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UpdateCurrentUserHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\UploadUserImageHandler;
use OpenDxp\Bundle\AdminBundle\Helper\User as UserHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
class UserProfileController extends AdminAbstractController
{
    #[Route('/user/upload-current-user-image', name: 'opendxp_admin_user_uploadcurrentuserimage', methods: ['POST'])]
    public function uploadCurrentUserImageAction(
        Request $request,
        UploadUserImageHandler $uploadUserImage,
        #[MapQueryParameter] int $id,
    ): JsonResponse {
        /** @var UploadedFile $avatarFile */
        $avatarFile = $request->files->get('Filedata');

        $uploadUserImage($id, $avatarFile);

        $response = $this->adminJson(ApiResponse::ok());
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/user/update-current-user', name: 'opendxp_admin_user_updatecurrentuser', methods: ['PUT'])]
    public function updateCurrentUserAction(Request $request, UpdateCurrentUserHandler $updateCurrentUser): JsonResponse
    {
        if (!$request->request->has('id')) {
            return $this->adminJson(false);
        }

        $isPasswordReset = \OpenDxp\Tool\Session::useBag($request->getSession(), static fn (AttributeBagInterface $adminSession) => (bool) $adminSession->get('password_reset'));

        $values = $this->decodeJson($request->request->get('data'), true);

        $keyBindingsJson = $request->request->has('keyBindings')
            ? $request->request->get('keyBindings')
            : null;

        $updateCurrentUser(
            requestedUserId: (int) $request->request->get('id'),
            values: $values,
            isPasswordReset: $isPasswordReset,
            keyBindingsJson: $keyBindingsJson,
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/user/get-current-user', name: 'opendxp_admin_user_getcurrentuser', methods: ['GET'])]
    public function getCurrentUserAction(Request $request, GetCurrentUserHandler $getCurrentUser): Response
    {
        $isPasswordReset = (bool) \OpenDxp\Tool\Session::useBag($request->getSession(), fn (AttributeBagInterface $adminSession) => $adminSession->get('password_reset'));

        $result = $getCurrentUser($isPasswordReset);

        $response = new Response('opendxp.currentuser = ' . $this->encodeJson($result->userData));
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    #[Route('/user/reset-my-2fa-secret', name: 'opendxp_admin_user_reset_my_2fa_secret', methods: ['PUT'])]
    public function resetMy2FaSecretAction(ResetMy2FaSecretHandler $resetMy2FaSecret): JsonResponse
    {
        $resetMy2FaSecret();

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/user/get-default-key-bindings', name: 'opendxp_admin_user_getdefaultkeybindings', methods: ['GET'])]
    public function getDefaultKeyBindingsAction(): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok(['data' => UserHelper::getDefaultKeyBindings()]));
    }
}
