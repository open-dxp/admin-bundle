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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Element\ElementInterface;

/**
 * @internal
 */
final class ObjectFieldGetter extends AbstractOperator
{
    private readonly string $attribute;

    private readonly string $forwardAttribute;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->attribute = $config->attribute ?? '';
        $this->forwardAttribute = $config->forwardAttribute ?? '';
    }

    public function getLabeledValue(array|ElementInterface $element): \stdClass
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        $getter = 'get' . ucfirst($this->attribute);

        if (!$children) {
            if ($this->attribute && method_exists($element, $getter)) {
                $result->value = $element->$getter();
                if ($result->value instanceof ElementInterface) {
                    $result->value = $result->value->getFullPath();
                }

                return $result;
            }
        } else {
            $c = $children[0];
            $forwardObject = $element;

            if ($this->forwardAttribute) {
                $forwardGetter = 'get' . ucfirst($this->forwardAttribute);
                if (method_exists($element, $forwardGetter)) {
                    $forwardObject = $element->$forwardGetter();
                    if (!$forwardObject) {
                        return $result;
                    }
                } else {
                    return $result;
                }
            }

            $valueContainer = $c->getLabeledValue($forwardObject);
            $value = $valueContainer->value ?? null;
            $result->value = $value;

            if (is_array($value)) {
                $newValues = [];
                foreach ($value as $o) {
                    if (!$o instanceof Concrete) {
                        continue;
                    }
                    if (!$this->attribute) {
                        continue;
                    }
                    if (!method_exists($o, $getter)) {
                        continue;
                    }
                    $targetValue = $o->$getter();
                    if (is_array($targetValue)) {
                        $newValues = [...$newValues, ...$targetValue];
                    } else {
                        $newValues[] = $targetValue;
                    }
                }
                $result->value = $newValues;
                $result->isArrayType = true;
            } elseif ($value instanceof Concrete) {
                $o = $value;
                if ($this->attribute && method_exists($o, $getter)) {
                    $value = $o->$getter();
                    $result->value = $value;
                }
            }
        }

        return $result;
    }
}
