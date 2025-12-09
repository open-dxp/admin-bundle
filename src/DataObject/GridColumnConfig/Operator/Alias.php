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
use stdClass;

/**
 * @internal
 */
final class Alias extends AbstractOperator
{
    public function getLabeledValue(array|ElementInterface $element): stdClass
    {
        $result = new stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        if (!$children) {
            return $result;
        }

        $c = $children[0];

        $valueArray = [];

        $childResult = $c->getLabeledValue($element);
        $isArrayType = $childResult->isArrayType ?? null;
        $childValues = $childResult->value;
        if ($childValues && !$isArrayType) {
            $childValues = [$childValues];
        }

        if ($childValues) {
            /** @var string $childValue */
            foreach ($childValues as $childValue) {
                $valueArray[] = $childValue;
            }
        }

        $result->isArrayType = $isArrayType;
        $result->value = $isArrayType ? $valueArray : $valueArray[0] ?? null;

        return $result;
    }
}
