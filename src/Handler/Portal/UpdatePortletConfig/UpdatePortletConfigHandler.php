<?php


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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\UpdatePortletConfig;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Portal\DashboardService;

final class UpdatePortletConfigHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly DashboardService $dashboardService,
    ) {
    }

    public function __invoke(UpdatePortletConfigPayload $payload): void
    {
        $user = $this->userContext->getAdminUser();
        $config = $this->dashboardService->getDashboard($user, $payload->dashboardKey);
        foreach ($config['positions'] as &$col) {
            foreach ($col as &$portlet) {
                if ($portlet['id'] === $payload->portletId) {
                    $portlet['config'] = $payload->configuration;

                    break;
                }
            }
        }

        $this->dashboardService->saveDashboard($user, $payload->dashboardKey, $config);
    }
}
