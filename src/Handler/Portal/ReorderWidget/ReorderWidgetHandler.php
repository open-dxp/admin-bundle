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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\ReorderWidget;

use OpenDxp\Bundle\AdminBundle\Factory\DashboardFactory;

final class ReorderWidgetHandler
{
    public function __construct(private readonly DashboardFactory $dashboardFactory)
    {
    }

    public function __invoke(ReorderWidgetPayload $payload): void
    {
        $dashboard = $this->dashboardFactory->create();

        $config = $dashboard->getDashboard($payload->dashboardId);
        $newConfig = [[], []];
        $colCount = 0;
        $toMove = null;

        foreach ($config['positions'] as $col) {
            foreach ($col as $item) {
                if ($item['id'] !== $payload->widgetId) {
                    $newConfig[$colCount][] = $item;
                } else {
                    $toMove = $item;
                }
            }
            $colCount++;
        }

        array_splice($newConfig[$payload->column], $payload->row, 0, [$toMove]);

        $config['positions'] = $newConfig;
        $dashboard->saveDashboard($payload->dashboardId, $config);
    }
}
