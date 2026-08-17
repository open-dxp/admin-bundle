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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\GetMinimalUser;

use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetMinimalUserHandler
{
    public function __invoke(GetMinimalUserPayload $payload): GetMinimalUserResult
    {
        $user = User::getById($payload->id);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        return new GetMinimalUserResult(
            id: (int) $user->getId(),
            admin: $user->isAdmin(),
            active: $user->isActive(),
            permissionInfo: [
                'assets' => $user->isAllowed('assets'),
                'documents' => $user->isAllowed('documents'),
                'objects' => $user->isAllowed('objects'),
            ],
        );
    }
}
