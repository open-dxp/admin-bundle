<?php

declare(strict_types=1);

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
