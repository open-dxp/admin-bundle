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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Service\GridData;

use OpenDxp;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Classificationstore;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Objectbrick;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Tool\Admin as AdminTool;
use OpenDxp\Tool\Session;
use stdClass;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Throwable;

/**
 * @internal
 */
class DataObject extends Element
{
    /**
     * Language only user for classification store !!!
     */
    public static function getData(AbstractObject $object, ?array $fields = null, ?string $requestedLanguage = null, array $params = []): array
    {
        $data = self::gridElementData($object);
        $csvMode = $params['csvMode'] ?? false;

        if ($object instanceof Concrete) {

            $user = AdminTool::getCurrentUser();

            $context = ['object' => $object,
                'purpose' => 'gridview',
                'language' => $requestedLanguage, ];
            $data['classname'] = $object->getClassName();
            $data['idPath'] = Service::getIdPath($object);
            $data['inheritedFields'] = [];
            $data['permissions'] = $object->getUserPermissions($user);
            $data['locked'] = $object->isLocked();

            if (is_null($fields)) {
                $fields = array_keys($object->getclass()->getFieldDefinitions());
            }

            $haveHelperDefinition = false;

            foreach ($fields as $key) {
                $brickDescriptor = null;
                $brickKey = null;
                $brickType = null;
                $brickGetter = null;
                $dataKey = $key;
                $keyParts = explode('~', $key);

                $def = $object->getClass()->getFieldDefinition($key, $context);

                if (str_starts_with($key, '#')) {
                    if (!$haveHelperDefinition) {
                        $helperDefinitions = self::getHelperDefinitions();
                        $haveHelperDefinition = true;
                    }
                    if (!empty($helperDefinitions[$key])) {
                        $context['fieldname'] = $key;
                        $data[$key] = \OpenDxp\Model\DataObject\Service::calculateCellValue($object, $helperDefinitions, $key, $context);
                    }
                } elseif (str_starts_with($key, '~')) {
                    $type = $keyParts[1];
                    if ($type === 'classificationstore') {
                        $data[$key] = self::getStoreValueForObject($object, $key, $requestedLanguage);
                    }
                } elseif (count($keyParts) > 1) {
                    // brick
                    $brickType = $keyParts[0];
                    if (str_contains($brickType, '?')) {
                        $brickDescriptor = substr($brickType, 1);
                        $brickDescriptor = json_decode($brickDescriptor, true);
                        $brickType = $brickDescriptor['containerKey'];
                    }

                    $brickKey = $keyParts[1];

                    $key = Service::getFieldForBrickType($object->getclass(), $brickType);

                    $brickClass = Objectbrick\Definition::getByKey($brickType);
                    $context['outerFieldname'] = $key;

                    if ($brickDescriptor) {
                        $innerContainer = $brickDescriptor['innerContainer'] ?? 'localizedfields';
                        /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $localizedFields */
                        $localizedFields = $brickClass->getFieldDefinition($innerContainer);
                        $def = $localizedFields->getFieldDefinition($brickDescriptor['brickfield']);
                    } elseif ($brickClass instanceof Objectbrick\Definition) {
                        $def = $brickClass->getFieldDefinition($brickKey, $context);
                    }
                }

                if (!empty($key)) {
                    // some of the not editable field require a special response
                    $getter = 'get' . ucfirst($key);
                    $needLocalizedPermissions = false;

                    // if the definition is not set try to get the definition from localized fields
                    if (!$def) {
                        /** @var Model\DataObject\ClassDefinition\Data\Localizedfields|null $locFields */
                        $locFields = $object->getClass()->getFieldDefinition('localizedfields');
                        if ($locFields) {
                            $def = $locFields->getFieldDefinition($key, $context);
                            if ($def) {
                                $needLocalizedPermissions = true;
                            }
                        }
                    }

                    //relation type fields with remote owner do not have a getter
                    if (method_exists($object, $getter)) {
                        //system columns must not be inherited
                        if (in_array($key, Concrete::SYSTEM_COLUMN_NAMES)) {
                            $data[$dataKey] = $object->$getter();
                        } else {
                            $valueObject = self::getValueForObject($object, $key, $brickType, $brickKey, $def, $context, $brickDescriptor, $requestedLanguage);
                            $data['inheritedFields'][$dataKey] = ['inherited' => $valueObject->objectid != $object->getId(), 'objectid' => $valueObject->objectid];

                            if ($csvMode || method_exists($def, 'getDataForGrid')) {
                                if ($brickKey) {
                                    $context['containerType'] = 'objectbrick';
                                    $context['containerKey'] = $brickType;
                                    $context['outerFieldname'] = $key;
                                }

                                $params = [...$params, 'context' => $context];
                                if (!isset($params['purpose'])) {
                                    $params['purpose'] = 'gridview';
                                }

                                if ($csvMode) {
                                    $getterParams = ['language' => $requestedLanguage];
                                    $tempData = $def->getForCsvExport($object, $getterParams);
                                } elseif (method_exists($def, 'getDataForGrid')) {
                                    $tempData = $def->getDataForGrid($valueObject->value, $object, $params);
                                } else {
                                    continue;
                                }

                                if ($def instanceof ClassDefinition\Data\Localizedfields) {
                                    $needLocalizedPermissions = true;
                                    foreach ($tempData as $tempKey => $tempValue) {
                                        $data[$tempKey] = $tempValue;
                                    }
                                } else {
                                    $data[$dataKey] = $tempData;
                                    if (
                                        $def instanceof Model\DataObject\ClassDefinition\Data\Select
                                        && !$def->useConfiguredOptions()
                                        && $def->getOptionsProviderClass()
                                    ) {
                                        $data[$dataKey . '%options'] = $def->getOptions();
                                    }

                                    // to prevent malforded grids in case of empty fieldcollections
                                    if ($def instanceof ClassDefinition\Data\Fieldcollections) {
                                        $data[$dataKey] ??= '';
                                    }
                                }
                            } else {
                                $data[$dataKey] = $valueObject->value;
                            }
                        }
                    }

                    // because the key for the classification store has not a direct getter, you have to check separately if the data is inheritable
                    if (
                        str_starts_with($key, '~') &&
                        ($keyParts[1] ?? null) === 'classificationstore'
                    ) {
                        $fieldDef = self::getClassificationStoreFieldDefinition($key);
                        $value = self::normalizeValue($data[$key]);

                        if ($fieldDef->isEmpty($value)) {
                            $inheritedData = static::getInheritedData($object, $key, $requestedLanguage);

                            if ($inheritedData !== []) {
                                $parent = $inheritedData['parent'];
                                $data[$dataKey] = $inheritedData['value'];
                                $data['inheritedFields'][$dataKey] = [
                                    'inherited' => $parent->getId() !== $object->getId(),
                                    'objectid'  => $parent->getId(),
                                ];
                            }
                        }
                    }

                    if ($needLocalizedPermissions && !$user->isAdmin()) {
                        $locale = OpenDxp::getContainer()->get(LocaleServiceInterface::class)->findLocale();
                        $permissionTypes = ['View', 'Edit'];
                        foreach ($permissionTypes as $permissionType) {
                            //TODO, this needs refactoring! Ideally, call it only once!
                            $languagesAllowed = Service::getLanguagePermissions($object, $user, 'l' . $permissionType);

                            if ($languagesAllowed) {
                                $languagesAllowed = array_keys($languagesAllowed);

                                if (!in_array($locale, $languagesAllowed)) {
                                    $data['metadata']['permission'][$key]['no' . $permissionType] = 1;
                                    if ($permissionType === 'View') {
                                        $data[$key] = null;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    public static function getHelperDefinitions(): array
    {
        $stack = OpenDxp::getContainer()->get('request_stack');
        if ($stack->getMainRequest()?->hasSession()) {
            $session = $stack->getSession();

            return Session::useBag($session, fn (AttributeBagInterface $session) => $session->get('helpercolumns', []), 'opendxp_gridconfig');
        }

        return [];
    }

    /**
     * gets value for given object and getter, including inherited values
     *
     * @return stdClass value and objectid where the value comes from
     */
    private static function getValueForObject(Concrete $object, string $key, ?string $brickType = null, ?string $brickKey = null, ?ClassDefinition\Data $fieldDefinition = null, array $context = [], ?array $brickDescriptor = null, ?string $requestedLanguage = null): stdClass
    {
        $getter = 'get' . ucfirst($key);
        $value = null;

        try {
            $value = $object->$getter($requestedLanguage ?? AdminTool::getCurrentUser()?->getLanguage());
        } catch (Throwable) {
        }

        if (empty($value)) {
            $value = $object->$getter();
        }
        if (!empty($value) && !empty($brickType)) {
            $getBrickType = 'get' . ucfirst($brickType);
            $value = $value->$getBrickType();
            if (!empty($value) && !empty($brickKey)) {
                if ($brickDescriptor) {
                    $innerContainer = $brickDescriptor['innerContainer'] ?? 'localizedfields';
                    $localizedFields = $value->{'get' . ucfirst($innerContainer)}();
                    $brickDefinition = Model\DataObject\Objectbrick\Definition::getByKey($brickType);
                    /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $fieldDefinitionLocalizedFields */
                    $fieldDefinitionLocalizedFields = $brickDefinition->getFieldDefinition('localizedfields');
                    $fieldDefinition = $fieldDefinitionLocalizedFields->getFieldDefinition($brickKey);
                    $value = $localizedFields->getLocalizedValue($brickDescriptor['brickfield']);
                } else {
                    $brickFieldGetter = 'get' . ucfirst($brickKey);
                    $value = $value->$brickFieldGetter();
                }
            }
        }

        if (!$fieldDefinition) {
            $fieldDefinition = $object->getClass()->getFieldDefinition($key, $context);
        }

        if (!empty($brickType) && !empty($brickKey) && !$brickDescriptor) {
            $brickClass = Objectbrick\Definition::getByKey($brickType);
            $context = ['object' => $object, 'outerFieldname' => $key];
            $fieldDefinition = $brickClass->getFieldDefinition($brickKey, $context);
        }

        if ($fieldDefinition->isEmpty($value) && $fieldDefinition->supportsInheritance()) {
            $parent = Service::hasInheritableParentObject($object);
            if (!empty($parent)) {
                return self::getValueForObject($parent, $key, $brickType, $brickKey, $fieldDefinition, $context, $brickDescriptor);
            }
        }

        $result = new stdClass();
        $result->value = $value;
        $result->objectid = $object->getId();

        return $result;
    }

    /**
     * gets store value for given object and key
     */
    private static function getStoreValueForObject(Concrete $object, string $key, ?string $requestedLanguage): mixed
    {
        $keyParts = explode('~', $key);

        if (str_starts_with($key, '~')) {
            $type = $keyParts[1];
            if ($type === 'classificationstore') {
                $field = $keyParts[2];
                $groupKeyId = explode('-', $keyParts[3]);

                $groupId = (int) $groupKeyId[0];
                $keyid = (int) $groupKeyId[1];
                $getter = 'get' . ucfirst($field);

                if (method_exists($object, $getter)) {
                    /** @var Classificationstore $classificationStoreData */
                    $classificationStoreData = $object->$getter();

                    /** @var Model\DataObject\ClassDefinition\Data\Classificationstore $csFieldDefinition */
                    $csFieldDefinition = $object->getClass()->getFieldDefinition($field);
                    $csLanguage = $requestedLanguage;

                    if (!$csFieldDefinition->isLocalized()) {
                        $csLanguage = 'default';
                    }

                    $fielddata = $classificationStoreData->getLocalizedKeyValue($groupId, $keyid, $csLanguage, true, true);

                    $keyConfig = Model\DataObject\Classificationstore\KeyConfig::getById($keyid);
                    $type = $keyConfig->getType();
                    $definition = json_decode($keyConfig->getDefinition(), true);
                    $definition = \OpenDxp\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);

                    if (method_exists($definition, 'getDataForGrid')) {
                        return $definition->getDataForGrid($fielddata, $object);
                    }

                    return $fielddata;
                }
            }
        }

        return null;
    }

    protected static function getInheritedData(Concrete $object, string $key, string $requestedLanguage): array
    {
        if (!$parent = Service::hasInheritableParentObject($object)) {
            return [];
        }

        $inheritedValue = self::normalizeValue(self::getStoreValueForObject($parent, $key, $requestedLanguage));

        if (!static::getClassificationStoreFieldDefinition($key)->isEmpty($inheritedValue)) {
            return [
                'parent' => $parent,
                'value' => $inheritedValue,
            ];
        }

        return self::getInheritedData($parent, $key, $requestedLanguage);
    }

    /**
     * The actual data could be a plain array for tables,
     * an associative array for RGB or Quantity Value where the value is a key.
     */
    private static function normalizeValue(mixed $data): mixed
    {
        if (is_array($data)) {
            if (array_is_list($data)) {
                return $data;
            }
            if (array_key_exists('value', $data)) {
                return $data['value'];
            }
        }

        return $data;
    }

    protected static function getClassificationStoreFieldDefinition(string $key): Data
    {
        $keyParts = explode('~', $key);
        $groupKeyId = explode('-', $keyParts[3]);

        $keyid = (int) $groupKeyId[1];

        $keyConfig = Model\DataObject\Classificationstore\KeyConfig::getById($keyid);
        $type = $keyConfig->getType();
        $definition = json_decode($keyConfig->getDefinition(), true);

        return \OpenDxp\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);
    }
}
