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

namespace OpenDxp\Bundle\AdminBundle\Handler\Admin\Statistics;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminStatisticsService;
use Throwable;

final class StatisticsHandler
{
    public function __construct(
        private readonly AdminStatisticsService $adminStatisticsService,
        private readonly ClientInterface $httpClient,
    ) {
    }

    public function __invoke(): void
    {
        try {
            $data = $this->adminStatisticsService->createStatistics()->asStatisticsArray();
        } catch (Throwable) {
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
