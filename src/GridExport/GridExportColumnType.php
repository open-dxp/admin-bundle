<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport;

enum GridExportColumnType: string
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case BOOLEAN = 'boolean';
    case DATE = 'date';
    case DATETIME = 'datetime';
}
