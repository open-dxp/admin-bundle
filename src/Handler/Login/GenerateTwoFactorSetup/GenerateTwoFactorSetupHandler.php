<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\GenerateTwoFactorSetup;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

final class GenerateTwoFactorSetupHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GoogleAuthenticatorInterface $twoFactor,
    ) {}

    public function __invoke(): GenerateTwoFactorSetupResult
    {
        $user = $this->userContext->getAdminUser();
        $proxyUser = $this->userContext->getAdminUserProxy();

        $secret = $this->twoFactor->generateSecret();

        $user->setTwoFactorAuthentication('enabled', true);
        $user->setTwoFactorAuthentication('type', 'google');
        $user->setTwoFactorAuthentication('secret', $secret);

        $url = $this->twoFactor->getQRContent($proxyUser);

        $qrResult = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->size(200)
            ->build();

        return new GenerateTwoFactorSetupResult(
            secret: $secret,
            qrDataUri: $qrResult->getDataUri(),
        );
    }
}
