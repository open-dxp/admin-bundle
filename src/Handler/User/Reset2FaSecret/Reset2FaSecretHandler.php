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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\Reset2FaSecret;

use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class Reset2FaSecretHandler
{
    public function __invoke(Reset2FaSecretPayload $payload): void
    {
        $user = User::getById($payload->id);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $user->setTwoFactorAuthentication('enabled', false);
        $user->setTwoFactorAuthentication('secret', '');
        $user->save();
    }
}
