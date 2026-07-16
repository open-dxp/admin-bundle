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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\CreateDashboard;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Portal\DashboardService;

final class CreateDashboardHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly DashboardService $dashboardService,
    ) {
    }

    public function __invoke(CreateDashboardPayload $payload): void
    {
        if (empty($payload->key)) {
            throw new AdminOperationFailedException('empty');
        }

        $user = $this->userContext->getAdminUser();

        $dashboards = $this->dashboardService->getAllDashboards($user);
        if (isset($dashboards[$payload->key])) {
            throw new AdminOperationFailedException('name_already_in_use');
        }

        $this->dashboardService->saveDashboard($user, $payload->key);
    }
}
