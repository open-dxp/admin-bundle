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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\GetCurrentUser;

use OpenDxp\Bundle\AdminBundle\Helper\User as UserHelper;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\PasswordResetSessionGateway;
use OpenDxp\Model\User;
use OpenDxp\Tool;

final class GetCurrentUserHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly PasswordResetSessionGateway $passwordResetSession,
    ) {}

    public function __invoke(GetCurrentUserPayload $payload): GetCurrentUserResult
    {
        $user = $this->userContext->getAdminUser();
        $list = new User\Permission\Definition\Listing();
        $definitions = $list->load();

        foreach ($definitions as $definition) {
            $user->setPermission($definition->getKey(), $user->isAllowed($definition->getKey()));
        }

        $userData = $user->getObjectVars();
        $contentLanguages = Tool\Admin::reorderWebsiteLanguages($user, Tool::getValidLanguages());
        $userData['contentLanguages'] = $contentLanguages;
        $userData['keyBindings'] = UserHelper::getDefaultKeyBindings($user);

        unset($userData['password'], $userData['passwordRecoveryToken']);
        $userData['twoFactorAuthentication'] = $user->getTwoFactorAuthentication();
        unset($userData['twoFactorAuthentication']['secret']);
        $userData['twoFactorAuthentication']['isActive'] = $user->getTwoFactorAuthentication('enabled') && $user->getTwoFactorAuthentication('secret');
        $userData['hasImage'] = $user->hasImage();
        $userData['isPasswordReset'] = $this->passwordResetSession->isPasswordReset();
        $userData['validLocales'] = Tool::getSupportedJSLocales();

        return new GetCurrentUserResult(userData: $userData);
    }
}
