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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\AddWidget;

use OpenDxp\Bundle\AdminBundle\Factory\DashboardFactory;

final class AddWidgetHandler
{
    public function __construct(private readonly DashboardFactory $dashboardFactory)
    {
    }

    public function __invoke(AddWidgetPayload $payload): AddWidgetResult
    {
        $dashboard = $this->dashboardFactory->create();

        $config = $dashboard->getDashboard($payload->dashboardId);

        $nextId = 0;
        foreach ($config['positions'] as $col) {
            foreach ($col as $row) {
                $nextId = ($row['id'] > $nextId ? $row['id'] : $nextId);
            }
        }

        $nextId += 1;
        $config['positions'][0][] = [
            'id' => $nextId,
            'type' => $payload->type,
            'config' => null,
        ];

        $dashboard->saveDashboard($payload->dashboardId, $config);

        return new AddWidgetResult(id: $nextId);
    }
}
