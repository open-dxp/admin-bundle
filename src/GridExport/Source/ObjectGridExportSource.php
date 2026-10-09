<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport\Source;

use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumn;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Mapper\GridData\DataObject as GridData;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Listing;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Model\User;
use OpenDxp\Security\CorePermission;
use OpenDxp\Tool\UserTimezone;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Exports the objects of a class with the columns of the object grid. DataObject\Service formats every value.
 */
#[AsGridExportSource(name: 'objects', permission: CorePermission::Objects->value, batchSize: 20)]
final class ObjectGridExportSource implements GridExportSourceInterface
{
    // The system keys of DataObject\Service are the titles of the header "name". They are unique within a row.
    private const string KEY_HEADER = 'name';

    private const string LABEL_HEADER = 'title';

    public function __construct(
        private readonly GridHelperService $gridHelperService,
        private readonly LocaleServiceInterface $localeService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {
    }

    /**
     * Takes the columns from the data of the first object, as the CSV export of DataObject\Service does. A listener of
     * DataObjectEvents::POST_CSV_ITEM_EXPORT can add columns to that data.
     */
    public function getColumns(GridExportQuery $query): array
    {
        $listing = $this->createListing($query);
        $listing->setLimit(1);
        $object = $listing->getObjects()[0] ?? null;

        if (!$object instanceof Concrete) {
            return array_map(
                static fn (array $field): GridExportColumn => new GridExportColumn($field['key'], $field['label']),
                $this->getFields($query),
            );
        }

        $helperDefinitions = GridData::getHelperDefinitions();

        return array_map(
            static fn (string $key, string $label): GridExportColumn => new GridExportColumn($key, $label),
            array_keys($this->getObjectData($object, $query, $helperDefinitions, self::KEY_HEADER)),
            array_keys($this->getObjectData($object, $query, $helperDefinitions, self::LABEL_HEADER)),
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

        $event = new GenericEvent($this->currentControllerContext->getController(), [
            'list' => $listing,
            'context' => $query->parameters,
        ]);
        $this->eventDispatcher->dispatch($event, AdminEvents::OBJECT_LIST_BEFORE_EXPORT);
        $listing = $event->getArgument('list');

        $inheritedValues = Concrete::getGetInheritedValues();
        $userTimezone = UserTimezone::getUserTimezone();
        $locale = $this->localeService->getLocale();
        Concrete::setGetInheritedValues(($query->parameters['enableInheritance'] ?? 'false') === 'true');
        UserTimezone::setUserTimezone($query->timezone);
        $this->localeService->setLocale($this->getLanguage($query));

        try {
            $helperDefinitions = GridData::getHelperDefinitions();

            foreach ($listing->getObjects() as $object) {
                if (!$object instanceof Concrete) {
                    continue;
                }

                yield array_map(
                    $this->formatValue(...),
                    $this->getObjectData($object, $query, $helperDefinitions, self::KEY_HEADER),
                );
            }
        } finally {
            Concrete::setGetInheritedValues($inheritedValues);
            UserTimezone::setUserTimezone($userTimezone);
            $this->localeService->setLocale($locale);
        }
    }

    private function createListing(GridExportQuery $query): Listing
    {
        $parameters = $query->parameters;
        $parameters['ids'] = $query->selectedIds;
        $parameters['batch'] = 'true';

        $listing = $this->gridHelperService->prepareListingForGrid(
            $parameters,
            $this->getLanguage($query),
            User::getById($query->userId),
        );

        $event = new GenericEvent($this->currentControllerContext->getController(), [
            'list' => $listing,
            'context' => $query->parameters,
        ]);
        $this->eventDispatcher->dispatch($event, AdminEvents::OBJECT_LIST_BEFORE_EXPORT_PREPARE);

        return $event->getArgument('list');
    }

    /**
     * @param array<string, mixed> $helperDefinitions
     *
     * @return array<string, mixed>
     */
    private function getObjectData(
        Concrete $object,
        GridExportQuery $query,
        array $helperDefinitions,
        string $header,
    ): array {
        return Service::getCsvDataForObject(
            $object,
            $this->getLanguage($query),
            $this->getFields($query),
            $helperDefinitions,
            $this->localeService,
            $header,
            true,
            ['source' => 'opendxp-export', ...($query->parameters['context'] ?? [])],
        );
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function getFields(GridExportQuery $query): array
    {
        return $query->parameters['columns'] ?? [];
    }

    private function getLanguage(GridExportQuery $query): string
    {
        return (string) ($query->parameters['language'] ?? $query->language);
    }

    private function formatValue(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };
    }
}
