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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal;

use OpenDxp\Bundle\AdminBundle\Factory\DashboardFactory;

final class UpdatePortletConfigHandler
{
    public function __construct(private readonly DashboardFactory $dashboardFactory)
    {
    }

    public function __invoke(string $dashboardKey, ?int $portletId, mixed $configuration): void
    {
        $dashboard = $this->dashboardFactory->create();

        $config = $dashboard->getDashboard($dashboardKey);
        foreach ($config['positions'] as &$col) {
            foreach ($col as &$portlet) {
                if ($portlet['id'] === $portletId) {
                    $portlet['config'] = $configuration;
                    break;
                }
            }
        }

        $dashboard->saveDashboard($dashboardKey, $config);
    }
}
