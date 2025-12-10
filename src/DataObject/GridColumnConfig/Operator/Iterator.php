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
final class Iterator extends AbstractOperator
{
    public function getLabeledValue(array|ElementInterface $element): stdClass
    {
        $result = new stdClass();
        $result->label = $this->label;
        $result->value = [];

        if (!is_array($element)) {
            return $result;
        }

        $children = $this->getChildren();

        if (!$children) {
            return $result;
        }

        $c = $children[0];

        $valueArray = [];

        foreach ($element as $subElement) {
            $childResult = $c->getLabeledValue($subElement);
            $valueArray[] = $childResult !== null && $childResult->value ? $childResult->value : null;
        }

        $result->value = $valueArray;

        return $result;
    }
}
