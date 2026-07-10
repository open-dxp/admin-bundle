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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\GetDashboardConfiguration;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Portal\DashboardService;

final class GetDashboardConfigurationHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly DashboardService $dashboardService,
    ) {
    }

    public function __invoke(GetDashboardConfigurationPayload $payload): GetDashboardConfigurationResult
    {
        $config = $this->dashboardService->getDashboard($this->userContext->getAdminUser(), $payload->key ?? 'welcome');

        return new GetDashboardConfigurationResult(config: $config);
    }
}
