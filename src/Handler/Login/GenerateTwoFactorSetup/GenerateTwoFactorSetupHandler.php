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

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\GenerateTwoFactorSetup;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\TwoFactorSetupSessionGateway;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

final class GenerateTwoFactorSetupHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GoogleAuthenticatorInterface $twoFactor,
        private readonly TwoFactorSetupSessionGateway $twoFactorSetupSession,
    ) {
    }

    public function __invoke(GenerateTwoFactorSetupPayload $payload): GenerateTwoFactorSetupResult
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

        $this->twoFactorSetupSession->storeSecret($secret);

        return new GenerateTwoFactorSetupResult(
            qrDataUri: $qrResult->getDataUri(),
        );
    }
}
