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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\UploadUserImage;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UploadUserImageHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    /**
     * @throws BadRequestHttpException
     */
    public function __invoke(UploadUserImagePayload $payload): void
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

        if ($userObj->isAdmin() && !$adminUser?->isAdmin()) {
            throw new AccessDeniedHttpException('Only admin users are allowed to modify admin users');
        }

        $assetType = Asset::getTypeFromMimeMapping($payload->avatarFile->getMimeType(), $payload->avatarFile->getFileName());
        if ($assetType !== 'image') {
            throw new BadRequestHttpException('Unsupported file format.');
        }

        $userObj->setImage($payload->avatarFile->getPathname());
    }
}
