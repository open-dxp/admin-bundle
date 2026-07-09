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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\GetUserImage;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetUserImageHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(GetUserImagePayload $payload): GetUserImageResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $currentUserId = $adminUser?->getId();

        // matches the old getUserId() helper: a differing target id requires the 'users' permission
        if ($payload->targetUserId !== null && $payload->targetUserId !== $currentUserId && !$adminUser?->isAllowed('users')) {
            throw new AccessDeniedHttpException();
        }

        $targetUserId = $payload->targetUserId ?? (int) $currentUserId;

        $userObj = User::getById($targetUserId);

        if (!$userObj) {
            throw new NotFoundHttpException('User not found');
        }

        return new GetUserImageResult(image: $userObj->getImage());
    }
}
