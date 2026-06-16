<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login;

use Exception;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

final class SaveTwoFactorSetupHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GoogleAuthenticatorInterface $twoFactor,
    ) {}

    public function __invoke(string $secret, string $authCode): void
    {
        if (!$secret) {
            throw new Exception('2fa secret not found');
        }

        $user = $this->userContext->getAdminUser();
        $proxyUser = $this->userContext->getAdminUserProxy();

        $user->setTwoFactorAuthentication('enabled', true);
        $user->setTwoFactorAuthentication('type', 'google');
        $user->setTwoFactorAuthentication('secret', $secret);

        if (!$this->twoFactor->checkCode($proxyUser, $authCode)) {
            throw new Exception('2fa_wrong');
        }

        $user->save();
    }
}
