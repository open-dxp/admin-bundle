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

namespace OpenDxp\Bundle\AdminBundle\Repository;

use OpenDxp\Model\User;
use OpenDxp\Tool\Serialize;
use Symfony\Component\Filesystem\Filesystem;

final class DashboardRepository
{
    public function load(User $user): array
    {
        if (!is_file($this->getConfigFile($user))) {
            return [];
        }

        return Serialize::unserialize(
            file_get_contents($this->getConfigFile($user)),
            ['allowed_classes' => false]
        ) ?: [];
    }

    public function save(User $user, array $dashboards): void
    {
        $filesystem = new Filesystem();
        if (!is_dir($this->getConfigDir())) {
            $filesystem->mkdir($this->getConfigDir(), 0775);
        }

        $filesystem->dumpFile($this->getConfigFile($user), Serialize::serialize($dashboards));
    }

    private function getConfigDir(): string
    {
        return OPENDXP_CONFIGURATION_DIRECTORY . '/portal';
    }

    private function getConfigFile(User $user): string
    {
        return $this->getConfigDir() . '/dashboards_' . $user->getId() . '.psf';
    }
}
