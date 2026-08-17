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

namespace OpenDxp\Bundle\AdminBundle\Session\Gateway;

use OpenDxp\Bundle\AdminBundle\Session\SessionGatewayInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Backs the two-step 2FA setup wizard.
 */
final class TwoFactorSetupSessionGateway implements SessionGatewayInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function storeSecret(string $secret): void
    {
        $this->requestStack->getSession()->set('2fa_secret', $secret);
    }

    public function getSecret(): string
    {
        return (string) $this->requestStack->getSession()->get('2fa_secret');
    }
}
