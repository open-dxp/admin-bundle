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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetUserImageHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(GetUserImagePayload $payload): GetUserImageResult
    {
        $targetUserId = $payload->targetUserId ?? (int) $this->userContext->getAdminUser()?->getId();

        $userObj = User::getById($targetUserId);

        if (!$userObj) {
            throw new NotFoundHttpException('User not found');
        }

        return new GetUserImageResult(image: $userObj->getImage());
    }
}
