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
final class Boolean extends AbstractOperator
{
    private bool $skipNull;

    private string $operator;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->skipNull = $config->skipNull ?? false;
        $this->operator = $config->operator ?? '';
    }

    public function getLabeledValue(array|ElementInterface $element): \stdClass
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        if ($this->getOperator() !== 'and' && $this->getOperator() !== 'or') {
            return $result;
        }

        if (!$children) {
            return $result;
        }

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
            } elseif (!$this->skipNull) {
                $valueArray[] = null;
            }
        }

        $resultValue = current($valueArray);
        foreach ($valueArray as $val) {
            $resultValue = $this->getOperator() === 'and' ? $val && $resultValue : $val || $resultValue;
        }

        $result->value = $resultValue;

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

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): void
    {
        $this->operator = $operator;
    }
}
