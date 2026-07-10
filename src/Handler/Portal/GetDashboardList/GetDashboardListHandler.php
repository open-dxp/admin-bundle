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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\GetDashboardList;

use OpenDxp\Bundle\AdminBundle\Payload\Common\EmptyPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Portal\DashboardService;

final class GetDashboardListHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly DashboardService $dashboardService,
    ) {
    }

    public function __invoke(EmptyPayload $payload): GetDashboardListResult
    {
        $allDashboards = $this->dashboardService->getAllDashboards($this->userContext->getAdminUser());

        $dashboards = [];
        foreach (array_keys($allDashboards) as $key) {
            if ($key !== 'welcome') {
                $dashboards[] = $key;
            }
        }

        return new GetDashboardListResult(dashboards: $dashboards);
    }
}
