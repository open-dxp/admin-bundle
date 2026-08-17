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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\SaveTwoFactorSetup;

use Exception;
use OpenDxp\Bundle\AdminBundle\Exception\Login\TwoFactorCodeInvalidException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\TwoFactorSetupSessionGateway;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

final class SaveTwoFactorSetupHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GoogleAuthenticatorInterface $twoFactor,
        private readonly TwoFactorSetupSessionGateway $twoFactorSetupSession,
    ) {
    }

    public function __invoke(SaveTwoFactorSetupPayload $payload): void
    {
        $secret = $this->twoFactorSetupSession->getSecret();

        if (!$secret) {
            throw new Exception('2fa secret not found');
        }

        $user = $this->userContext->getAdminUser();
        $proxyUser = $this->userContext->getAdminUserProxy();

        $user->setTwoFactorAuthentication('enabled', true);
        $user->setTwoFactorAuthentication('type', 'google');
        $user->setTwoFactorAuthentication('secret', $secret);

        if (!$this->twoFactor->checkCode($proxyUser, $payload->authCode)) {
            throw new TwoFactorCodeInvalidException('2fa_wrong');
        }

        $user->save();
    }
}
