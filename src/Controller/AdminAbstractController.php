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

namespace OpenDxp\Bundle\AdminBundle\Controller;

use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Model\User;
use OpenDxp\Security\User\User as UserProxy;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @internal
 */
abstract class AdminAbstractController extends UserAwareController
{
    use JsonHelperTrait;

    /**
     * Returns a JsonResponse that uses the admin serializer
     */
    protected function adminJson(mixed $data, int $status = 200, array $headers = [], array $context = [], bool $useAdminSerializer = true): JsonResponse
    {
        return $this->jsonResponse($data, $status, $headers, $context, $useAdminSerializer);
    }

    /**
     * Get user from user proxy object which is registered on security component
     */
    protected function getAdminUser(bool $proxyUser = false): UserProxy|User|null
    {
        return $this->getOpenDxpUser($proxyUser);
    }
}
