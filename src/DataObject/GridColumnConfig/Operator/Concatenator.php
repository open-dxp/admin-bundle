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
final class Concatenator extends AbstractOperator
{
    private readonly string $glue;

    private readonly bool $forceValue;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->glue = $config->glue ?? '';
        $this->forceValue = $config->forceValue ?? false;
    }

    public function getLabeledValue(array|ElementInterface $element): \stdClass
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $hasValue = true;
        if (!$this->forceValue) {
            $hasValue = false;
        }

        $children = $this->getChildren();
        $valueArray = [];

        foreach ($children as $c) {
            $childResult = $c->getLabeledValue($element);
            $childValues = (array)($childResult->value ?? []);

            foreach ($childValues as $value) {
                if (!$hasValue) {
                    $hasValue = is_object($value) && method_exists($value, 'isEmpty') ? !$value->isEmpty() : !empty($value);
                }

                if ($value !== null) {
                    $valueArray[] = $value;
                }
            }
        }

        if ($hasValue) {
            $result->value = implode($this->glue, $valueArray);

            return $result;
        }

        $result->empty = true;

        return $result;
    }
}
