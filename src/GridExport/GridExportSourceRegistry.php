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

namespace OpenDxp\Bundle\AdminBundle\GridExport;

use InvalidArgumentException;
use OpenDxp\Bundle\AdminBundle\DependencyInjection\Compiler\GridExportSourcePass;
use Psr\Container\ContainerInterface;

/**
 * @see GridExportSourcePass
 */
final class GridExportSourceRegistry
{
    public const string TAG = 'opendxp_admin.grid_export_source';

    /**
     * @param array<string, string> $permissions
     * @param array<string, int> $batchSizes
     */
    public function __construct(
        private readonly ContainerInterface $sources,
        private readonly array $permissions,
        private readonly array $batchSizes,
    ) {
    }

    public function hasSource(string $name): bool
    {
        return $this->sources->has($name);
    }

    public function getSource(string $name): GridExportSourceInterface
    {
        if (!$this->hasSource($name)) {
            throw new InvalidArgumentException(sprintf('No grid export source is registered as "%s".', $name));
        }

        return $this->sources->get($name);
    }

    public function getPermission(string $name): string
    {
        return $this->permissions[$name] ?? throw new InvalidArgumentException(
            sprintf('No grid export source is registered as "%s".', $name),
        );
    }

    public function getBatchSize(string $name): int
    {
        return $this->batchSizes[$name] ?? throw new InvalidArgumentException(
            sprintf('No grid export source is registered as "%s".', $name),
        );
    }
}
