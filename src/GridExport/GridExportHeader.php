<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport;

enum GridExportHeader: string
{
    case LABEL = 'title';
    case KEY = 'name';
    case NONE = 'no_header';

    /**
     * @param list<GridExportColumn> $columns
     *
     * @return list<string>|null
     */
    public function getTitles(array $columns): ?array
    {
        return match ($this) {
            self::LABEL => array_map(static fn (GridExportColumn $column): string => $column->label, $columns),
            self::KEY => array_map(static fn (GridExportColumn $column): string => $column->key, $columns),
            self::NONE => null,
        };
    }
}
