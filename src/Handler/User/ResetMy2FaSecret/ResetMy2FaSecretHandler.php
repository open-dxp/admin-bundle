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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\ResetMy2FaSecret;

use OpenDxp\Bundle\AdminBundle\Payload\Common\EmptyPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class ResetMy2FaSecretHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(EmptyPayload $payload): void
    {
        $user = $this->userContext->getAdminUser();
        $user->setTwoFactorAuthentication('required', true);
        $user->setTwoFactorAuthentication('enabled', false);
        $user->setTwoFactorAuthentication('secret', '');
        $user->save();
    }
}
