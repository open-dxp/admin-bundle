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

namespace OpenDxp\Bundle\AdminBundle\Service\Admin;

use Doctrine\DBAL\Connection;
use OpenDxp\Bundle\AdminBundle\Dto\Admin\StatisticsDto;
use OpenDxp\Version;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

final class AdminStatisticsService
{
    public function __construct(
        private readonly Connection $db,
        private readonly KernelInterface $kernel,
        private readonly InstanceIdentityService $instanceIdentity,
    ) {
    }

    public function createStatistics(): StatisticsDto
    {
        try {
            $dbVersion = $this->db->fetchOne('SELECT VERSION()');
        } catch (Throwable) {
            $dbVersion = null;
        }

        return new StatisticsDto(
            instanceId: $this->instanceIdentity->getInstanceId(),
            systemUuid: $this->instanceIdentity->getSystemUuid($this->kernel->getEnvironment()),
            revision: Version::getRevision(),
            version: Version::getVersion(),
            majorVersion: Version::getMajorVersion(),
            phpVersion: PHP_VERSION,
            dbVersion: is_string($dbVersion) ? $dbVersion : null,
            bundles: array_keys($this->kernel->getBundles()),
            environment: $this->kernel->getEnvironment(),
        );
    }
}
