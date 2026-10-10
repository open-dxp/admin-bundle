<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport\Source;

use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumn;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Bundle\AdminBundle\Service\Translation\TranslationQueryService;
use OpenDxp\Model\Translation;

abstract class AbstractTranslationGridExportSource implements GridExportSourceInterface
{
    public function __construct(private readonly TranslationQueryService $translationQueryService)
    {
    }

    abstract protected function getDomain(GridExportQuery $query): string;

    /**
     * @return list<string>
     */
    abstract protected function getLanguages(GridExportQuery $query): array;

    public function getColumns(GridExportQuery $query): array
    {
        // The import finds a column by its title, so every title is the key of its column.
        return array_map(
            static fn (string $key): GridExportColumn => new GridExportColumn($key, $key),
            ['key', ...$this->getLanguages($query)],
        );
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

        $languages = $this->getLanguages($query);

        foreach ($listing->getTranslations() as $translation) {
            $row = ['key' => $translation->getKey()];
            foreach ($languages as $language) {
                $row[$language] = $translation->getTranslation($language);
            }

            yield $row;
        }
    }

    private function createListing(GridExportQuery $query): Translation\Listing
    {
        $filter = $query->parameters['filter'] ?? null;
        $searchString = $query->parameters['searchString'] ?? null;

        return $this->translationQueryService->createListing(
            $this->getDomain($query),
            $this->getLanguages($query),
            $query->parameters,
            $filter === null ? null : (string) $filter,
            $searchString === null ? null : (string) $searchString,
        );
    }
}
