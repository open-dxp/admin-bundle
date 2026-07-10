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

namespace OpenDxp\Bundle\AdminBundle\Service\Portal;

use OpenDxp\Bundle\AdminBundle\Perspective\Config;
use OpenDxp\Bundle\AdminBundle\Repository\DashboardRepository;
use OpenDxp\Model\User;

final class DashboardService
{
    public function __construct(private readonly DashboardRepository $repository) {}

    public function getAllDashboards(User $user): array
    {
        return $this->mergeWithPredefined($this->repository->load($user));
    }

    public function getDashboard(User $user, string $key = 'welcome'): array
    {
        $dashboards = $this->getAllDashboards($user);
        $dashboard = $dashboards[$key] ?? null;

        if ($dashboard) {
            $disabledPortlets = array_keys($this->getDisabledPortlets($user));
            $positions = $dashboard['positions'];
            if (is_array($positions)) {
                foreach ($positions as $columnKey => $column) {
                    if ($column) {
                        foreach ($column as $portletKey => $portletCfg) {
                            $type = $portletCfg['type'];
                            if (in_array($type, $disabledPortlets)) {
                                unset($dashboard['positions'][$columnKey][$portletKey]);
                            }
                        }
                    }
                }
            }
        }

        return $dashboard ?: ['positions' => [[], []]];
    }

    public function saveDashboard(User $user, string $key, ?array $configuration = null): void
    {
        $dashboards = $this->getAllDashboards($user);
        $dashboards[$key] = $configuration ?: ['positions' => [[], []]];

        $this->repository->save($user, $dashboards);
    }

    public function deleteDashboard(User $user, string $key): void
    {
        $dashboards = $this->getAllDashboards($user);
        unset($dashboards[$key]);

        $this->repository->save($user, $dashboards);
    }

    public function getDisabledPortlets(User $user): array
    {
        $perspectiveCfg = Config::getRuntimePerspective($user);
        $dashboardCfg = $perspectiveCfg['dashboards'] ?? [];

        return $dashboardCfg['disabledPortlets'] ?? [];
    }

    private function mergeWithPredefined(array $dashboards): array
    {
        $perspectiveCfg = Config::getRuntimePerspective();
        $dashboardCfg = $perspectiveCfg['dashboards'] ?? [];
        $predefined = $dashboardCfg['predefined'] ?? [];

        if (empty($dashboards)) {
            return $predefined;
        }

        foreach ($predefined as $key => $dashboard) {
            if (!isset($dashboards[$key])) {
                $dashboards[$key] = $dashboard;
            }
        }

        return $dashboards;
    }
}
