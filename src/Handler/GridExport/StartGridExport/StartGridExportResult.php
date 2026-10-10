<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\GridExport\StartGridExport;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class StartGridExportResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public int $total,
        public int $batchSize,
        public int $batchCount,
    ) {
    }
}
