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

namespace OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use OpenDxp\Model\Element\ElementInterface;
use stdClass;

/**
 * @internal
 */
final class CaseConverter extends AbstractOperator
{
    private readonly int $capitalization;

    public function __construct(stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->capitalization = $config->capitalization ?? 0;
    }

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

        $childValues = $childResult->value ?? null;
        $isArrayType = is_array($childValues);

        if ($childValues && !is_array($childValues)) {
            $childValues = [$childValues];
        }

        if (is_array($childValues)) {
            foreach ($childValues as $childValue) {
                if ($this->capitalization === 1) {
                    $childValue = strtoupper($childValue);
                } elseif ($this->capitalization === -1) {
                    $childValue = strtolower($childValue);
                }
                $valueArray[] = $childValue;
            }
        } else {
            $valueArray[] = null;
        }

        $result->value = $isArrayType ? $valueArray : $valueArray[0];

        return $result;
    }
}
