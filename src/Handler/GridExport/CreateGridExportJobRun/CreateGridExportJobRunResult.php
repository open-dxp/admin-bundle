<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\GridExport\CreateGridExportJobRun;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class CreateGridExportJobRunResult implements ResultInterface
{
    public function __construct(
        public int $jobRunId,
    ) {
    }
}
