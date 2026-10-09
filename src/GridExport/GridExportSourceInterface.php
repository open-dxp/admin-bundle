<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport;

use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;

/**
 * Provides the rows of a grid for an export. A source reads the rows the same way the grid lists them.
 *
 * @see AsGridExportSource
 */
interface GridExportSourceInterface
{
    /**
     * @return list<GridExportColumn>
     */
    public function getColumns(GridExportQuery $query): array;

    public function countRows(GridExportQuery $query): int;

    /**
     * Returns the rows of one batch. Every row holds the value of each column under the key of the column.
     *
     * A value matches the type of its column:
     * - `string` for GridExportColumnType::STRING
     * - `int` for GridExportColumnType::INTEGER
     * - `int` or `float` for GridExportColumnType::FLOAT
     * - `bool` for GridExportColumnType::BOOLEAN
     * - `DateTimeInterface` for GridExportColumnType::DATE and GridExportColumnType::DATETIME
     * - `null` for an empty value of any type
     *
     * @return iterable<array<string, mixed>>
     */
    public function getRows(GridExportQuery $query, int $offset, int $limit): iterable;
}
