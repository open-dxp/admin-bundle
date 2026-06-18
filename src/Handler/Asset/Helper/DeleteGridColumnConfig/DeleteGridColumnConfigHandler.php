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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\DeleteGridColumnConfig;

use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\DeleteGridColumnConfig\DeleteGridColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class DeleteGridColumnConfigHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(DeleteGridColumnConfigPayload $payload): void
    {
        $gridConfigId = $payload->gridConfigId;
        $adminUser = $this->userContext->getAdminUser();
        $gridConfig = GridConfig::getById($gridConfigId);
        if (!$gridConfig) {
            throw new NotFoundHttpException('Grid config not found: ' . $gridConfigId);
        }

        if ($gridConfig->getOwnerId() !== $adminUser->getId()) {
            throw new BadRequestHttpException("don't mess with someone elses grid config");
        }

        $gridConfig->delete();
    }
}
