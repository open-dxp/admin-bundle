<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Service\Grid;

use Exception;
use OpenDxp\Bundle\AdminBundle\Dto\Grid\DataObjectGridColumnConfig;
use OpenDxp\Bundle\AdminBundle\Model\GridConfigFavourite;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\GridColumnConfigSessionGateway;
use OpenDxp\Config;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;
use OpenDxp\Tool;

final class DataObjectGridColumnConfigResolver
{
    public const array SYSTEM_COLUMNS = ['id', 'fullpath', 'key', 'published', 'creationDate', 'modificationDate', 'filename', 'classname'];

    public function __construct(
        private readonly GridColumnConfigService $gridColumnConfigService,
        private readonly Config $config,
        private readonly AdminUserContextInterface $userContext,
        private readonly GridColumnConfigSessionGateway $gridColumnConfigSession,
    ) {}

    public function resolve(string $locale, array $params, bool $isDelete = false): DataObjectGridColumnConfig
    {
        $user = $this->userContext->getAdminUser();
        $class = null;
        $fields = null;

        if ($params['id'] !== null) {
            $class = DataObject\ClassDefinition::getById($params['id']);
        } elseif ($params['name'] !== null) {
            $class = DataObject\ClassDefinition::getByName($params['name']);
        }

        $gridType = $params['gridtype'] ?? 'search';
        $objectId = $params['objectId'] !== null ? (int) $params['objectId'] : 0;

        if ($objectId) {
            $fields = DataObject\Service::getCustomGridFieldDefinitions($class->getId(), $objectId);
        }

        $context = ['purpose' => 'gridconfig'];
        if ($class) {
            $context['class'] = $class;
        }
        if ($objectId) {
            $context['object'] = DataObject::getById($objectId);
        }

        if (!$fields && $class) {
            $fields = $class->getFieldDefinitions();
        }

        $types = $params['types'] !== null ? explode(',', $params['types']) : [];
        $userId = $user?->getId() ?? 0;
        $requestedGridConfigId = $isDelete ? null : $params['gridConfigId'];
        $searchType = $params['searchType'];

        if ((string) ($requestedGridConfigId ?? '') === '' && $class) {
            $favourite = GridConfigFavourite::getByOwnerAndClassAndObjectId($userId, $class->getId(), $objectId ?: 0, $searchType);
            if (!$favourite && $objectId) {
                $favourite = GridConfigFavourite::getByOwnerAndClassAndObjectId($userId, $class->getId(), 0, $searchType);
            }
            if ($favourite) {
                $requestedGridConfigId = $favourite->getGridConfigId();
            }
        }

        $configData = $this->gridColumnConfigService->loadVerifiedGridConfig($requestedGridConfigId, $user);
        $gridConfig = $configData->config;

        $localizedFields = [];
        if (is_array($fields)) {
            foreach ($fields as $field) {
                if ($field instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $localizedFields[] = $field;
                }
            }
        }

        $availableFields = [];
        if ($configData->isEmpty()) {
            $availableFields = $this->getDefaultGridFields(
                $params['noSystemColumns'],
                $class,
                $gridType,
                $params['noBrickColumns'],
                $fields,
                $context,
                $objectId,
                $types
            );
        } else {
            $savedColumns = $gridConfig['columns'];
            foreach ($savedColumns as $key => $sc) {
                if (!$sc['hidden']) {
                    if (in_array($key, self::SYSTEM_COLUMNS, true)) {
                        $colConfig = [
                            'key' => $key,
                            'type' => 'system',
                            'label' => $key,
                            'position' => $sc['position'],
                        ];
                        $colConfig = $this->injectCustomLayoutValues($colConfig, $sc);
                        $availableFields[] = $colConfig;
                    } else {
                        $keyParts = explode('~', $key);

                        if (str_starts_with($key, '~')) {
                            $type = $keyParts[1];
                            $groupAndKeyId = explode('-', $keyParts[3]);
                            $keyId = (int) $groupAndKeyId[1];

                            if ($type === 'classificationstore') {
                                $keyDef = DataObject\Classificationstore\KeyConfig::getById($keyId);
                                if ($keyDef) {
                                    $keyFieldDef = json_decode($keyDef->getDefinition(), true);
                                    if ($keyFieldDef) {
                                        $keyFieldDef = DataObject\Classificationstore\Service::getFieldDefinitionFromJson($keyFieldDef, $keyDef->getType());
                                        $fieldConfig = $this->getFieldGridConfig($keyFieldDef, $gridType, (string) $sc['position'], true, null, $class, $objectId);
                                        if ($fieldConfig) {
                                            $fieldConfig['key'] = $key;
                                            $fieldConfig['label'] = '#' . $keyFieldDef->getTitle();
                                            $fieldConfig = $this->injectCustomLayoutValues($fieldConfig, $sc);
                                            $availableFields[] = $fieldConfig;
                                        }
                                    }
                                }
                            }
                        } elseif (count($keyParts) > 1) {
                            $brick = $keyParts[0];
                            $brickDescriptor = null;

                            if (str_contains($brick, '?')) {
                                $brickDescriptor = substr($brick, 1);
                                $brickDescriptor = json_decode($brickDescriptor, true);
                                $keyPrefix = $brick . '~';
                                $brick = $brickDescriptor['containerKey'];
                            } else {
                                $keyPrefix = $brick . '~';
                            }

                            $fieldname = $keyParts[1];
                            $brickClass = DataObject\Objectbrick\Definition::getByKey($brick);

                            $fd = null;
                            if ($brickClass instanceof DataObject\Objectbrick\Definition) {
                                if ($brickDescriptor) {
                                    $innerContainer = $brickDescriptor['innerContainer'] ?? 'localizedfields';
                                    /** @var DataObject\ClassDefinition\Data\Localizedfields $localizedField */
                                    $localizedField = $brickClass->getFieldDefinition($innerContainer);
                                    $fd = $localizedField->getFieldDefinition($brickDescriptor['brickfield']);
                                } else {
                                    $fd = $brickClass->getFieldDefinition($fieldname);
                                }
                            }

                            if ($fd !== null) {
                                $fieldConfig = $this->getFieldGridConfig($fd, $gridType, (string) $sc['position'], true, $keyPrefix, $class, $objectId);
                                if (!empty($fieldConfig)) {
                                    $fieldConfig = $this->injectCustomLayoutValues($fieldConfig, $sc);
                                    $availableFields[] = $fieldConfig;
                                }
                            }
                        } elseif (DataObject\Service::isHelperGridColumnConfig($key)) {
                            $calculatedColumnConfig = $this->getCalculatedColumnConfig($sc);
                            if ($calculatedColumnConfig) {
                                $availableFields[] = $calculatedColumnConfig;
                            }
                        } else {
                            $fd = $class->getFieldDefinition($key);
                            if (empty($fd)) {
                                foreach ($localizedFields as $lf) {
                                    $fd = $lf->getFieldDefinition($key);
                                    if (!empty($fd)) {
                                        break;
                                    }
                                }
                            }

                            if (!empty($fd)) {
                                $fieldConfig = $this->getFieldGridConfig($fd, $gridType, (string) $sc['position'], true, null, $class, $objectId);
                                if (!empty($fieldConfig)) {
                                    $fieldConfig = $this->injectCustomLayoutValues($fieldConfig, $sc);
                                    $availableFields[] = $fieldConfig;
                                }
                            }
                        }
                    }
                }
            }
        }

        usort($availableFields, static fn ($a, $b) => $a['position'] <=> $b['position']);

        $frontendLanguages = Tool\Admin::reorderWebsiteLanguages($user, $this->config['general']['valid_languages']);
        $language = $frontendLanguages ? $frontendLanguages[0] : $locale;
        if (!Tool::isValidLanguage($language)) {
            $validLanguages = Tool::getValidLanguages();
            $language = $validLanguages[0];
        }
        if (!empty($gridConfig) && !empty($gridConfig['language'])) {
            $language = $gridConfig['language'];
        }

        $availableConfigs = $class ? $this->gridColumnConfigService->getMyOwnColumnConfigs($userId, $class->getId(), $searchType) : [];
        $sharedConfigs = $class ? $this->gridColumnConfigService->getSharedColumnConfigs($user, $class->getId(), $searchType) : [];

        $settings = $this->gridColumnConfigService->buildBaseSettings($configData);
        $owner = null;
        if ($configData->ownerId) {
            $ownerObject = User::getById($configData->ownerId);
            $owner = $ownerObject instanceof User ? $ownerObject->getName() : (string) $configData->ownerId;
        }
        $settings['owner'] = $owner;
        $settings['modificationDate'] = $configData->modificationDate;
        $settings['saveFilters'] = $configData->isEmpty() ? null : $configData->saveFilters;
        $settings['allowVariants'] = $class && $class->getAllowVariants();

        $gridContext = $gridConfig['context'] ?? null;
        if ($gridContext) {
            $gridContext = json_decode($gridContext, true);
        }

        return new DataObjectGridColumnConfig(
            availableFields: $availableFields,
            settings: $settings,
            availableConfigs: $availableConfigs,
            sharedConfigs: $sharedConfigs,
            sortinfo: $gridConfig['sortinfo'] ?? false,
            onlyDirectChildren: $gridConfig['onlyDirectChildren'] ?? false,
            pageSize: $gridConfig['pageSize'] ?? false,
            context: $gridContext,
            language: $language,
            searchFilter: $gridConfig['searchFilter'] ?? '',
            filter: $gridConfig['filter'] ?? [],
        );
    }

    /**
     * @param DataObject\ClassDefinition\Data[]|null $fields
     */
    private function getDefaultGridFields(bool $noSystemColumns, ?DataObject\ClassDefinition $class, string $gridType, bool $noBrickColumns, ?array $fields, array $context, int $objectId, array $types = []): array
    {
        $count = 0;
        $availableFields = [];

        if (!$noSystemColumns && $class) {
            $vis = $class->getPropertyVisibility();
            foreach (self::SYSTEM_COLUMNS as $sc) {
                $key = $sc === 'fullpath' ? 'path' : $sc;

                if ($types === [] && (!empty($vis[$gridType][$key]) || $gridType === 'all')) {
                    $availableFields[] = [
                        'key' => $sc,
                        'type' => 'system',
                        'label' => $sc,
                        'position' => $count,
                    ];
                    $count++;
                }
            }
        }

        $includeBricks = !$noBrickColumns;

        if (is_array($fields)) {
            foreach ($fields as $field) {
                if ($field instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    foreach ($field->getFieldDefinitions($context) as $fd) {
                        if ($types === [] || in_array($fd->getFieldType(), $types)) {
                            $fieldConfig = $this->getFieldGridConfig($fd, $gridType, (string) $count, false, null, $class, $objectId);
                            if (!empty($fieldConfig)) {
                                $availableFields[] = $fieldConfig;
                                $count++;
                            }
                        }
                    }
                } elseif ($field instanceof DataObject\ClassDefinition\Data\Objectbricks && $includeBricks) {
                    if (in_array($field->getFieldType(), $types)) {
                        $fieldConfig = $this->getFieldGridConfig($field, $gridType, (string) $count, false, null, $class, $objectId);
                        if (!empty($fieldConfig)) {
                            $availableFields[] = $fieldConfig;
                            $count++;
                        }
                    } else {
                        $allowedTypes = $field->getAllowedTypes();
                        foreach ($allowedTypes as $t) {
                            $brickClass = DataObject\Objectbrick\Definition::getByKey($t);
                            $brickFields = $brickClass->getFieldDefinitions($context);
                            $this->appendBrickFields($field, $brickFields, $availableFields, $gridType, $count, $t, $class, $objectId);
                        }
                    }
                } elseif ($types === [] || in_array($field->getFieldType(), $types)) {
                    $fieldConfig = $this->getFieldGridConfig($field, $gridType, (string) $count, $types !== [], null, $class, $objectId);
                    if (!empty($fieldConfig)) {
                        $availableFields[] = $fieldConfig;
                        $count++;
                    }
                }
            }
        }

        return $availableFields;
    }

    /**
     * @param DataObject\ClassDefinition\Data[] $brickFields
     */
    private function appendBrickFields(DataObject\ClassDefinition\Data $field, array $brickFields, array &$availableFields, string $gridType, int &$count, string $brickType, DataObject\ClassDefinition $class, int $objectId, ?array $context = null): void
    {
        foreach ($brickFields as $bf) {
            if ($bf instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                $localizedFieldDefinitions = $bf->getFieldDefinitions();
                $localizedContext = [
                    'containerKey' => $brickType,
                    'fieldname' => $field->getName(),
                ];
                $this->appendBrickFields($bf, $localizedFieldDefinitions, $availableFields, $gridType, $count, $brickType, $class, $objectId, $localizedContext);
            } else {
                if ($context) {
                    $context['brickfield'] = $bf->getName();
                    $keyPrefix = '?' . json_encode($context) . '~';
                } else {
                    $keyPrefix = $brickType . '~';
                }
                $fieldConfig = $this->getFieldGridConfig($bf, $gridType, (string) $count, false, $keyPrefix, $class, $objectId);
                if (!empty($fieldConfig)) {
                    $availableFields[] = $fieldConfig;
                    $count++;
                }
            }
        }
    }

    private function injectCustomLayoutValues(array $fieldConfig, array $savedColumn): array
    {
        foreach (['width', 'locked'] as $key) {
            if (isset($savedColumn[$key])) {
                $fieldConfig[$key] = $savedColumn[$key];
            }
        }

        if (isset($savedColumn['fieldConfig']['layout']['noteditable'])) {
            $fieldConfig['layout']->setNoteditable($savedColumn['fieldConfig']['layout']['noteditable']);
        }

        return $fieldConfig;
    }

    private function getCalculatedColumnConfig(array $config): mixed
    {
        try {
            $existingKey = $config['fieldConfig']['key'];
            $calculatedColumnConfig['key'] = $existingKey;
            $calculatedColumnConfig['position'] = $config['position'];
            $calculatedColumnConfig['isOperator'] = true;
            $calculatedColumnConfig['attributes'] = $config['fieldConfig']['attributes'];
            $calculatedColumnConfig['width'] = $config['width'];
            $calculatedColumnConfig['locked'] = $config['locked'];

            $existingColumns = $this->gridColumnConfigSession->getHelperColumns();

            if (isset($existingColumns[$existingKey])) {
                return $calculatedColumnConfig;
            }

            $newKey = '#' . uniqid('', false);
            $calculatedColumnConfig['key'] = $newKey;

            $phpConfig = json_encode($config['fieldConfig']);
            $phpConfig = json_decode($phpConfig);
            $helperColumns = [$newKey => $phpConfig, ...$existingColumns];
            $this->gridColumnConfigSession->setHelperColumns($helperColumns);

            return $calculatedColumnConfig;
        } catch (Exception $e) {
            Logger::error((string) $e);
        }

        return null;
    }

    private function getFieldGridConfig(DataObject\ClassDefinition\Data $field, string $gridType, string $position, bool $force = false, ?string $keyPrefix = null, ?DataObject\ClassDefinition $class = null, ?int $objectId = null): ?array
    {
        $key = $keyPrefix . $field->getName();
        $config = null;
        $title = !empty($field->getTitle()) ? $field->getTitle() : $field->getName();

        if ($field instanceof DataObject\ClassDefinition\Data\Slider) {
            $config['minValue'] = $field->getMinValue();
            $config['maxValue'] = $field->getMaxValue();
            $config['increment'] = $field->getIncrement();
        }

        if (method_exists($field, 'getWidth')) {
            $config['width'] = $field->getWidth();
        }
        if (method_exists($field, 'getHeight')) {
            $config['height'] = $field->getHeight();
        }

        $visible = match ($gridType) {
            'search' => $field->getVisibleSearch(),
            'grid' => $field->getVisibleGridView(),
            default => true,
        };

        if (!$field->getInvisible() && ($force || $visible)) {
            $context = ['purpose' => 'gridconfig'];
            if ($class) {
                $context['class'] = $class;
            }
            if ($objectId) {
                $context['object'] = DataObject::getById($objectId);
            }
            DataObject\Service::enrichLayoutDefinition($field, null, $context);

            $result = [
                'key' => $key,
                'type' => $field->getFieldType(),
                'label' => $title,
                'config' => $config,
                'layout' => $field,
                'position' => $position,
            ];

            if ($field instanceof DataObject\ClassDefinition\Data\EncryptedField) {
                $result['delegateDatatype'] = $field->getDelegateDatatype();
            }

            return $result;
        }

        return null;
    }
}
