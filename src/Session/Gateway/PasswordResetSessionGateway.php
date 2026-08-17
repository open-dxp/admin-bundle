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
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Reads the "password_reset" flag set on the admin session bag during a forced-password-reset login flow.
 */
final class PasswordResetSessionGateway implements SessionGatewayInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function isPasswordReset(): bool
    {
        return (bool) Tool\Session::useBag(
            $this->requestStack->getSession(),
            static fn (AttributeBagInterface $adminSession) => $adminSession->get('password_reset'),
            self::BAG_ADMIN,
        );
    }
}
