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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\UploadUserImage;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UploadUserImageHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    /**
     * @throws BadRequestHttpException
     */
    public function __invoke(UploadUserImagePayload $payload): void
    {
        $adminUser = $this->userContext->getAdminUser();
        $targetUserId = $payload->targetUserId ?? (int) $adminUser?->getId();

        $userObj = User::getById($targetUserId);
        if (!$userObj) {
            throw new NotFoundHttpException('User not found');
        }

        if (!$adminUser?->isAdmin()) {
            if ($userObj->isAdmin()) {
                throw new AccessDeniedHttpException('Only admin users are allowed to modify admin users');
            }
            if ($adminUser?->getId() !== $userObj->getId()) {
                throw new AccessDeniedHttpException('Only admin users are allowed to modify users other than themselves');
            }
        }

        $assetType = Asset::getTypeFromMimeMapping($payload->avatarFile->getMimeType(), $payload->avatarFile->getFileName());
        if ($assetType !== 'image') {
            throw new BadRequestHttpException('Unsupported file format.');
        }

        $userObj->setImage($payload->avatarFile->getPathname());
    }
}
