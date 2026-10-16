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

namespace OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Value;

use Exception;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Objectbrick;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Model\Element\ElementInterface;
use stdClass;

/**
 * @internal
 */
final class DefaultValue extends AbstractValue
{
    private LocaleServiceInterface $localeService;

    public function setLocaleService(LocaleServiceInterface $localeService): void
    {
        $this->localeService = $localeService;
    }

    /**
     * @throws Exception
     */
    private function getValueForObject(Concrete $object, string $key, ?string $brickType = null, ?string $brickKey = null): stdClass
    {
        if (!$key) {
            throw new Exception('Empty key');
        }

        $fieldDefinition = null;
        if (!empty($brickType)) {
            $getter = 'get' . Service::getFieldForBrickType($object->getClass(), $brickType);
            $value = $object->$getter();

            $getBrickType = 'get' . ucfirst($brickType);
            $value = $value->$getBrickType();
            if (!empty($value) && !empty($brickKey)) {
                $brickGetter = 'get' . ucfirst($brickKey);
                $value = $value->$brickGetter();

                $brickClass = Objectbrick\Definition::getByKey($brickType);
                $context = ['object' => $object, 'outerFieldname' => $key];
                $fieldDefinition = $brickClass->getFieldDefinition($brickKey, $context);
            }
        } else {
            $getter = 'get' . ucfirst($key);
            $value = $object->$getter();

            $fieldDefinition = $object->getClass()->getFieldDefinition($key);

            if (!$fieldDefinition) {
                $localizedFields = $object->getClass()->getFieldDefinition('localizedfields');
                if ($localizedFields instanceof Data\Localizedfields) {
                    $fieldDefinition = $localizedFields->getFieldDefinition($key);
                }
            }
        }

        if (!$fieldDefinition instanceof Data) {
            return $this->getDefaultValue($value);
        }

        if ($fieldDefinition->isEmpty($value)) {
            $parent = Service::hasInheritableParentObject($object);

            if (!empty($parent)) {
                return $this->getValueForObject($parent, $key, $brickType, $brickKey);
            }
        }

        $result = new stdClass();
        $result->value = $value;
        $result->label = $fieldDefinition->getTitle();
        $result->def = $fieldDefinition;
        $result->empty = $fieldDefinition->isEmpty($value);
        $result->objectid = $object->getId();

        return $result;
    }

    private function getGridColumnValueForObject(Concrete $object, string $key): ?stdClass
    {
        $gridColumn = Service::findGridColumn($object->getClass(), $key);

        if (!$gridColumn) {
            return null;
        }

        $language = $this->localeService->getLocale() ?? 'default';
        $value = Service::useInheritedValues(
            true,
            static fn () => $gridColumn->getValue($object, ['language' => $language]),
        );

        $result = new stdClass();
        $result->value = $value;
        $result->label = $gridColumn->definition->getTitle();
        $result->def = $gridColumn->definition;
        $result->empty = $gridColumn->definition->isEmpty($value);
        $result->objectid = $object->getId();

        return $result;
    }

    private function getDefaultValue(mixed $value): stdClass
    {
        $result = new stdClass();
        $result->value = $value;
        $result->label = $this->label;
        $result->def = null;

        if (empty($value) || (is_object($value) && method_exists($value, 'isEmpty') && $value->isEmpty())) {
            $result->empty = true;
        } else {
            $result->empty = false;
        }

        return $result;
    }

    public function getLabeledValue(array|ElementInterface $element): ?stdClass
    {
        $attributeParts = explode('~', $this->attribute);

        $getter = 'get' . ucfirst($this->attribute);
        $brickType = null;
        $brickKey = null;

        if ($element instanceof Concrete) {
            if (str_starts_with($this->attribute, '~')) {
                return $this->getGridColumnValueForObject($element, $this->attribute);
            }
            if (count($attributeParts) > 1) {
                $json = json_decode(trim($attributeParts[0], '?'));
                $brickType = $json ? $json->containerKey : $attributeParts[0];
                $brickKey = $attributeParts[1];

                $getter = 'get' . Service::getFieldForBrickType($element->getClass(), $brickType);
            }
        }

        if ($this->attribute && method_exists($element, $getter)) {
            if ($element instanceof Concrete) {
                try {
                    $result = $this->getValueForObject($element, $this->attribute, $brickType, $brickKey);
                } catch (Exception) {
                    $result = $this->getDefaultValue($element->$getter());
                }
            } else {
                $result = $this->getDefaultValue($element->$getter());
            }

            return $result;
        }

        return null;
    }
}
