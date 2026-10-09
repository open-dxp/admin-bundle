<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Application\GridExport;

use DateTimeImmutable;
use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumn;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumnType;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Security\CorePermission;

#[AsGridExportSource(name: 'mock', permission: CorePermission::Objects->value, batchSize: 2)]
final class MockGridExportSource implements GridExportSourceInterface
{
    public function getColumns(GridExportQuery $query): array
    {
        return [
            new GridExportColumn('id', 'ID', GridExportColumnType::INTEGER),
            new GridExportColumn('name', 'Name'),
            new GridExportColumn('price', 'Price', GridExportColumnType::FLOAT),
            new GridExportColumn('active', 'Active', GridExportColumnType::BOOLEAN),
            new GridExportColumn('released', 'Released', GridExportColumnType::DATE),
            new GridExportColumn('updated', 'Updated', GridExportColumnType::DATETIME),
        ];
    }

    public function countRows(GridExportQuery $query): int
    {
        return count($this->getSelectedRows($query));
    }

    public function getRows(GridExportQuery $query, int $offset, int $limit): iterable
    {
        return array_slice($this->getSelectedRows($query), $offset, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getSelectedRows(GridExportQuery $query): array
    {
        $rows = [
            [
                'id' => 1,
                'name' => 'Ada',
                'price' => 9.5,
                'active' => true,
                'released' => new DateTimeImmutable('2024-01-15'),
                'updated' => new DateTimeImmutable('2024-01-15 10:30:00'),
            ],
            [
                'id' => 2,
                'name' => '=HYPERLINK("https://example.com")',
                'price' => null,
                'active' => false,
                'released' => null,
                'updated' => null,
            ],
            [
                'id' => 3,
                'name' => 'Jürgen',
                'price' => 3,
                'active' => true,
                'released' => new DateTimeImmutable('2024-02-01'),
                'updated' => new DateTimeImmutable('2024-02-01 08:00:00'),
            ],
        ];

        if ($query->selectedIds === []) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => in_array((string) $row['id'], $query->selectedIds, true),
        ));
    }
}
