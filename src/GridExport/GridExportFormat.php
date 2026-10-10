<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport;

enum GridExportFormat: string
{
    case CSV = 'csv';
    case XLSX = 'xlsx';

    public function getContentType(): string
    {
        return match ($this) {
            self::CSV => 'text/csv; charset=UTF-8',
            self::XLSX => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }
}
