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
