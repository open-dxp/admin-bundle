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
final class Base64 extends AbstractOperator
{
    private readonly string $mode;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->mode = $config->mode ?? '';
    }

    public function getLabeledValue(array|ElementInterface $element): \stdClass
    {
        $result = new \stdClass();
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
                $valueArray[] = $childValue;
            }
        } else {
            $valueArray[] = null;
        }

        $result->value = $isArrayType ? $valueArray : $valueArray[0];

        if ($this->mode === 'e') {
            $result->value = base64_encode($result->value);
        } elseif ($this->mode === 'd') {
            $result->value = base64_decode($result->value);
        }

        return $result;
    }
}
