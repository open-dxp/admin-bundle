<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport;

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
}
