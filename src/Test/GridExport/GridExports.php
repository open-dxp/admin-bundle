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

namespace OpenDxp\Bundle\AdminBundle\Test\GridExport;

use OpenDxp;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSettings;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Model\User;

final class GridExports
{
    /**
     * @param array<string, mixed> $parameters
     * @param list<int|string> $selectedIds
     */
    public static function export(
        User $user,
        string $source,
        array $parameters = [],
        GridExportSettings $settings = new GridExportSettings(),
        array $selectedIds = [],
        string $language = 'en',
        string $timezone = 'UTC',
    ): GridExportFile {
        $service = OpenDxp::getContainer()->get('test.service_container')->get(GridExportService::class);

        $export = $service->createExport(
            $source,
            new GridExportQuery(
                userId: $user->getId(),
                language: $language,
                timezone: $timezone,
                parameters: $parameters,
                selectedIds: array_map(strval(...), $selectedIds),
            ),
            $settings,
        );

        for ($batch = 0; $batch < $export->getBatchCount(); $batch++) {
            $service->writeBatch($export, $batch);
        }

        $response = $service->createDownload($export);
        $path = $response->getFile()->getPathname();
        $content = (string) file_get_contents($path);
        unlink($path);

        preg_match('/filename="?([^";]+)"?/', (string) $response->headers->get('Content-Disposition'), $filename);

        return new GridExportFile($filename[1] ?? '', $content, $settings->delimiter);
    }
}
