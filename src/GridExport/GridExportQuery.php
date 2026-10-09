<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport;

/**
 * Describes the rows of a grid to export.
 */
final readonly class GridExportQuery
{
    /**
     * @param array<string, mixed> $parameters
     * @param list<int|string> $selectedIds
     */
    public function __construct(
        public int $userId,
        public string $language,
        public string $timezone,
        public array $parameters = [],
        public array $selectedIds = [],
    ) {
    }
}
