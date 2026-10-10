<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport;

final readonly class GridExportColumn
{
    public function __construct(
        public string $key,
        public string $label,
        public GridExportColumnType $type = GridExportColumnType::STRING,
    ) {
    }
}
