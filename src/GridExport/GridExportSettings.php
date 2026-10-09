<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport;

final readonly class GridExportSettings
{
    public function __construct(
        public GridExportFormat $format = GridExportFormat::CSV,
        public GridExportHeader $header = GridExportHeader::LABEL,
        public string $delimiter = ';',
    ) {
    }
}
