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

namespace OpenDxp\Bundle\AdminBundle\Service\Admin;

use Doctrine\DBAL\Connection;
use OpenDxp\Bundle\AdminBundle\Dto\Admin\StatisticsDto;
use OpenDxp\Version;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\KernelInterface;

final class AdminStatisticsService
{
    public function __construct(
        private readonly Connection $db,
        private readonly KernelInterface $kernel,
        #[Autowire('%secret%')]
        private readonly string $secret,
    ) {}

    public function createStatistics(): StatisticsDto
    {
        try {
            $dbVersion = $this->db->fetchOne('SELECT VERSION()');
        } catch (\Throwable) {
            $dbVersion = null;
        }

        return new StatisticsDto(
            instanceId: $this->buildInstanceId(),
            revision: Version::getRevision(),
            version: Version::getVersion(),
            majorVersion: Version::getMajorVersion(),
            phpVersion: PHP_VERSION,
            dbVersion: is_string($dbVersion) ? $dbVersion : null,
            bundles: array_keys($this->kernel->getBundles()),
        );
    }

    private function buildInstanceId(): string
    {
        try {
            return sha1(substr($this->secret, 3, -3));
        } catch (\Exception) {
            return 'not-set';
        }
    }
}
