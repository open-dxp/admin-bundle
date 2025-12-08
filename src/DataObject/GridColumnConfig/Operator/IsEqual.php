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

use OpenDxp\Model\Element\ElementInterface;

/**
 * @internal
 */
final class IsEqual extends AbstractOperator
{
    private bool $skipNull;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->skipNull = $config->skipNull ?? false;
    }

    public function getLabeledValue(array|ElementInterface $element): \stdClass
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        if (!$children) {
            return $result;
        }

        $isEqual = true;
        $valueArray = [];
        foreach ($children as $c) {
            $childResult = $c->getLabeledValue($element);
            $isArrayType = $childResult->isArrayType ?? false;
            $childValues = $childResult->value ?? null;
            if ($childValues && !$isArrayType) {
                $childValues = [$childValues];
            }

            if (is_array($childValues)) {
                foreach ($childValues as $value) {
                    if (is_null($value) && $this->skipNull) {
                        continue;
                    }
                    $valueArray[] = $value;
                }
            } else {
                if (!$this->skipNull) {
                    $valueArray[] = null;
                }
            }
        }

        $firstValue = current($valueArray);
        foreach ($valueArray as $val) {
            if ($firstValue !== $val) {
                $isEqual = false;

                break;
            }
        }

        $result->value = $isEqual;

        return $result;
    }

    public function getSkipNull(): bool
    {
        return $this->skipNull;
    }

    public function setSkipNull(bool $skipNull): void
    {
        $this->skipNull = $skipNull;
    }
}
