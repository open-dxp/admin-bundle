<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\SaveTwoFactorSetup;

use Exception;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

final class SaveTwoFactorSetupHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GoogleAuthenticatorInterface $twoFactor,
    ) {}

    public function __invoke(SaveTwoFactorSetupPayload $payload): void
    {
        if (!$payload->secret) {
            throw new Exception('2fa secret not found');
        }

        $user = $this->userContext->getAdminUser();
        $proxyUser = $this->userContext->getAdminUserProxy();

        $user->setTwoFactorAuthentication('enabled', true);
        $user->setTwoFactorAuthentication('type', 'google');
        $user->setTwoFactorAuthentication('secret', $payload->secret);

        if (!$this->twoFactor->checkCode($proxyUser, $payload->authCode)) {
            throw new Exception('2fa_wrong');
        }

        $user->save();
    }
}
