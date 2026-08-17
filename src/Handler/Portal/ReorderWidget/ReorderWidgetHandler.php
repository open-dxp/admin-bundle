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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\ReorderWidget;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Portal\DashboardService;

final class ReorderWidgetHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly DashboardService $dashboardService,
    ) {
    }

    public function __invoke(ReorderWidgetPayload $payload): void
    {
        $user = $this->userContext->getAdminUser();
        $config = $this->dashboardService->getDashboard($user, $payload->dashboardId);
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
        $this->dashboardService->saveDashboard($user, $payload->dashboardId, $config);
    }
}
