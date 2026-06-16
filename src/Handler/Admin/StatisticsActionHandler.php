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

namespace OpenDxp\Bundle\AdminBundle\Handler\Admin;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use OpenDxp\Bundle\AdminBundle\Builder\AdminSettingsAssembler;

final class StatisticsActionHandler
{
    public function __construct(
        private readonly AdminSettingsAssembler $factory,
        private readonly ClientInterface $httpClient,
    ) {}

    public function __invoke(): void
    {
        try {
            $dto = $this->factory->createStatistics();
            $data = [
                'instance_id'   => $dto->instanceId,
                'revision'      => $dto->revision,
                'version'       => $dto->version,
                'major_version' => $dto->majorVersion,
                'php_version'   => $dto->phpVersion,
                'db_version'    => $dto->dbVersion,
                'bundles'       => $dto->bundles,
            ];
        } catch (\Throwable) {
            $data = [];
        }

        try {
            $this->httpClient->request(
                'POST',
                'https://metrics.opendxp.io/statistics',
                ['json' => $data],
            );
        } catch (GuzzleException) {
            // fail silently
        }
    }
}
