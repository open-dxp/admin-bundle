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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\AddWidget;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Portal\DashboardService;

final class AddWidgetHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly DashboardService $dashboardService,
    ) {
    }

    public function __invoke(AddWidgetPayload $payload): AddWidgetResult
    {
        $user = $this->userContext->getAdminUser();
        $config = $this->dashboardService->getDashboard($user, $payload->dashboardId);

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

        $this->dashboardService->saveDashboard($user, $payload->dashboardId, $config);

        return new AddWidgetResult(id: $nextId);
    }
}
