<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport;

use LogicException;

final readonly class GridExport
{
    /**
     * @param list<GridExportColumn> $columns
     */
    public function __construct(
        public string $id,
        public string $source,
        public GridExportQuery $query,
        public GridExportSettings $settings,
        public array $columns,
        public int $total,
        public int $batchSize,
    ) {
    }

    public function getBatchCount(): int
    {
        return (int) ceil($this->total / $this->batchSize);
    }

    /**
     * Returns the values of a row of the source in the order of the columns.
     *
     * @param array<string, mixed> $row
     *
     * @return list<mixed>
     */
    public function getValues(array $row): array
    {
        $values = [];
        foreach ($this->columns as $column) {
            if (!array_key_exists($column->key, $row)) {
                throw new LogicException(sprintf(
                    'The grid export source "%s" returns a row without the column "%s".',
                    $this->source,
                    $column->key,
                ));
            }

            $values[] = $row[$column->key];
        }

        $undeclared = array_diff(array_keys($row), array_column($this->columns, 'key'));
        if ($undeclared !== []) {
            throw new LogicException(sprintf(
                'The grid export source "%s" returns a row with the undeclared columns "%s".',
                $this->source,
                implode('", "', $undeclared),
            ));
        }

        return $values;
    }
}
