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

use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Maintenance\TaskInterface;

/**
 * Deletes the batches of every export that has expired. A user who closes the admin during an export leaves them.
 */
final class GridExportCleanupTask implements TaskInterface
{
    public function __construct(
        private readonly GridExportService $gridExportService,
        private readonly GridExportBatchStorage $batchStorage,
    ) {
    }

    public function execute(): void
    {
        foreach ($this->batchStorage->getExportIds() as $id) {
            if ($this->gridExportService->findExport($id) === null) {
                $this->batchStorage->deleteBatches($id);
            }
        }
    }
}
