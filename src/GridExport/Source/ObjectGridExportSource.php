<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\GridExport\Source;

use InvalidArgumentException;
use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumn;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Bundle\AdminBundle\GridExport\SessionGridExportSourceInterface;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\GridColumnConfigSessionGateway;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Listing;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Model\User;
use OpenDxp\Security\CorePermission;
use OpenDxp\Tool\UserTimezone;
use stdClass;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsGridExportSource(name: 'objects', permission: CorePermission::Objects->value, batchSize: 20)]
final class ObjectGridExportSource implements GridExportSourceInterface, SessionGridExportSourceInterface
{
    // The system keys of DataObject\Service are the titles of the header "name". They are unique within a row.
    private const string KEY_HEADER = 'name';

    private const string LABEL_HEADER = 'title';

    // A key of this prefix names an operator column. Its definition exists only in the session.
    private const string OPERATOR_KEY_PREFIX = '#';

    public function __construct(
        private readonly GridHelperService $gridHelperService,
        private readonly LocaleServiceInterface $localeService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CurrentControllerContextInterface $currentControllerContext,
        private readonly GridColumnConfigSessionGateway $gridColumnConfigSession,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Keeps the definitions of the operator columns as JSON, because the TmpStore unserializes no stdClass.
     */
    public function getSessionParameters(GridExportQuery $query): array
    {
        if (!$this->requestStack->getCurrentRequest()?->hasSession()) {
            return [];
        }

        $helperDefinitions = array_intersect_key(
            $this->gridColumnConfigSession->getHelperColumns(),
            array_flip(array_column($this->getFields($query), 'key')),
        );

        return ['helperDefinitions' => json_encode($helperDefinitions, JSON_THROW_ON_ERROR)];
    }

    /**
     * Takes the columns from the data of the first object, as the CSV export of DataObject\Service does. A listener of
     * DataObjectEvents::POST_CSV_ITEM_EXPORT can add columns to that data.
     */
    public function getColumns(GridExportQuery $query): array
    {
        $helperDefinitions = $this->getHelperDefinitions($query);

        $listing = $this->createListing($query);
        $listing->setLimit(1);
        $object = $listing->getObjects()[0] ?? null;

        if (!$object instanceof Concrete) {
            return array_map(
                static fn (array $field): GridExportColumn => new GridExportColumn($field['key'], $field['label']),
                $this->getFields($query),
            );
        }

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
            $helperDefinitions = $this->getHelperDefinitions($query);

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
     * @return array<string, stdClass>
     */
    private function getHelperDefinitions(GridExportQuery $query): array
    {
        // An empty list of definitions comes back as an array, any other as an object.
        $helperDefinitions = (array) json_decode(
            $query->sessionParameters['helperDefinitions'] ?? '[]',
            flags: JSON_THROW_ON_ERROR,
        );

        foreach ($this->getFields($query) as $field) {
            $isOperator = str_starts_with($field['key'], self::OPERATOR_KEY_PREFIX);
            if ($isOperator && !isset($helperDefinitions[$field['key']])) {
                throw new InvalidArgumentException(sprintf(
                    'The object grid export has no definition of the operator column "%s".',
                    $field['key'],
                ));
            }
        }

        return $helperDefinitions;
    }

    /**
     * @param array<string, stdClass> $helperDefinitions
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
            [
                'source' => 'opendxp-export',
                ...($query->parameters['context'] ?? []),
                'user' => User::getById($query->userId),
            ],
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
