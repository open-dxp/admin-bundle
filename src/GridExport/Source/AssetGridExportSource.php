<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport\Source;

use DateTimeImmutable;
use DateTimeZone;
use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumn;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumnType;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User;
use OpenDxp\Security\CorePermission;

#[AsGridExportSource(name: 'assets', permission: CorePermission::Assets->value, batchSize: 100)]
final class AssetGridExportSource implements GridExportSourceInterface
{
    // A preview is an image and has no value to export.
    private const string PREVIEW_COLUMN = 'preview~system';

    private const array SYSTEM_COLUMN_TYPES = [
        'id~system' => GridExportColumnType::INTEGER,
        'size~system' => GridExportColumnType::INTEGER,
        'creationDate~system' => GridExportColumnType::DATETIME,
        'modificationDate~system' => GridExportColumnType::DATETIME,
    ];

    public function __construct(private readonly GridHelperService $gridHelperService)
    {
    }

    public function getColumns(GridExportQuery $query): array
    {
        $columns = [];
        foreach ($query->parameters['columns'] ?? [] as $column) {
            if ($column['key'] === self::PREVIEW_COLUMN) {
                continue;
            }

            $columns[] = new GridExportColumn(
                $column['key'],
                $column['label'],
                self::SYSTEM_COLUMN_TYPES[$column['key']] ?? GridExportColumnType::STRING,
            );
        }

        return $columns;
    }

    public function countRows(GridExportQuery $query): int
    {
        return $this->createListing($query)->getTotalCount();
    }

    public function getRows(GridExportQuery $query, int $offset, int $limit): iterable
    {
        $listing = $this->createListing($query);
        $listing->setOffset($offset);
        $listing->setLimit($limit);

        $columns = $this->getColumns($query);
        $language = str_replace('default', '', (string) ($query->parameters['language'] ?? ''));
        $timezone = new DateTimeZone($query->timezone);

        foreach ($listing->getAssets() as $asset) {
            $row = [];
            foreach ($columns as $column) {
                $row[$column->key] = $this->getValue($asset, $column, $language, $timezone);
            }

            yield $row;
        }
    }

    private function createListing(GridExportQuery $query): Asset\Listing
    {
        $listing = $this->gridHelperService->prepareAssetListingForGrid(
            $query->parameters,
            User::getById($query->userId),
        );

        if ($query->selectedIds !== []) {
            $listing->addConditionParam('id IN (?)', [array_map(intval(...), $query->selectedIds)]);
        }

        return $listing;
    }

    private function getValue(
        Asset $asset,
        GridExportColumn $column,
        string $language,
        DateTimeZone $timezone,
    ): DateTimeImmutable|int|string|null {
        [$name, $scope] = array_pad(explode('~', $column->key, 2), 2, null);

        if ($scope === 'system') {
            return match ($name) {
                'size' => $asset->getFileSize(),
                'creationDate' => $this->createDate($asset->getCreationDate(), $timezone),
                'modificationDate' => $this->createDate($asset->getModificationDate(), $timezone),
                default => $this->formatValue($asset->{'get' . ucfirst($name)}()),
            };
        }

        // A metadata column names its language after the tilde. The language "none" stands for no language.
        $metadataLanguage = $scope === null ? $language : str_replace('none', '', $scope);

        return $this->formatValue($asset->getMetadata($name, $metadataLanguage, true));
    }

    private function createDate(int $timestamp, DateTimeZone $timezone): DateTimeImmutable
    {
        return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone);
    }

    private function formatValue(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof ElementInterface => $value->getRealFullPath(),
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };
    }
}
