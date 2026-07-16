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

namespace OpenDxp\Bundle\AdminBundle\Dto\Admin;

final readonly class StatisticsDto
{
    public function __construct(
        public string $instanceId,
        public string $revision,
        public string $version,
        public int $majorVersion,
        public string $phpVersion,
        public ?string $dbVersion,
        public array $bundles,
    ) {}

    public function asStatisticsArray(): array
    {
        return [
            'instance_id'   => $this->instanceId,
            'revision'      => $this->revision,
            'version'       => $this->version,
            'major_version' => $this->majorVersion,
            'php_version'   => $this->phpVersion,
            'db_version'    => $this->dbVersion,
            'bundles'       => $this->bundles,
        ];
    }
}
