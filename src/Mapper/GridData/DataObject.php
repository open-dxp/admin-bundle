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

namespace OpenDxp\Bundle\AdminBundle\Mapper\GridData;

use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\GridColumnConfigSessionGateway;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Objectbrick;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Tool\Admin as AdminTool;
use stdClass;
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

            $helperDefinitions = $params['helperDefinitions'] ?? [];

            foreach ($fields as $key) {
                $brickDescriptor = null;
                $brickKey = null;
                $brickType = null;
                $brickGetter = null;
                $dataKey = $key;
                $keyParts = explode('~', $key);

                $def = $object->getClass()->getFieldDefinition($key, $context);

                if (str_starts_with($key, '#')) {
                    if (!empty($helperDefinitions[$key])) {
                        $context['fieldname'] = $key;
                        $data[$key] = Service::calculateCellValue(
                            $object,
                            $helperDefinitions,
                            $key,
                            [...$context, 'user' => $user],
                        );
                    }
                } elseif (str_starts_with($key, '~')) {
                    $gridColumn = Service::findGridColumn($object->getClass(), $key);

                    if ($gridColumn) {
                        $data = self::addGridColumnData(
                            $data,
                            $gridColumn,
                            $object,
                            $requestedLanguage,
                            [...$params, 'context' => $context],
                        );
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

    /**
     * Static bridge for OpenDxp\Model\DataObject\Service, which cannot receive the gateway by injection.
     * Prefer GridColumnConfigSessionGateway::getHelperColumns() everywhere else.
     */
    public static function getHelperDefinitions(): array
    {
        $requestStack = OpenDxp::getContainer()->get('request_stack');

        if (!$requestStack->getMainRequest()?->hasSession()) {
            return [];
        }

        return (new GridColumnConfigSessionGateway($requestStack))->getHelperColumns();
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

    private static function addGridColumnData(
        array $data,
        Data\FieldGridColumn $gridColumn,
        Concrete $object,
        ?string $requestedLanguage,
        array $params,
    ): array {
        $definition = $gridColumn->definition;
        $getValue = static fn (Concrete $owner) => $gridColumn->getValue($owner, ['language' => $requestedLanguage]);
        $value = Service::useInheritedValues(true, $getValue, [$object]);
        $owner = $object;

        while (
            !$definition->isEmpty($value)
            && $definition->isEmpty(Service::useInheritedValues(false, $getValue, [$owner]))
            && ($parent = Service::hasInheritableParentObject($owner))
        ) {
            $owner = $parent;
        }

        $key = $gridColumn->getKey();
        $data[$key] = method_exists($definition, 'getDataForGrid')
            ? $definition->getDataForGrid($value, $object, $params)
            : $value;
        $data['inheritedFields'][$key] = [
            'inherited' => $owner->getId() !== $object->getId(),
            'objectid' => $owner->getId(),
        ];

        return $data;
    }
}
