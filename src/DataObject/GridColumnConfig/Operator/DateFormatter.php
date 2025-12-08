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

use Carbon\Carbon;
use OpenDxp\Model\Element\ElementInterface;

/**
 * @internal
 */
final class DateFormatter extends AbstractOperator
{
    private ?string $format = null;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->format = ($config->format ? $config->format : null);
    }

    public function getLabeledValue(array|ElementInterface $element): \stdClass
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->value = null;

        $children = $this->getChildren();

        if ($children) {
            $newChildrenResult = [];
            $isArrayType = null;

            foreach ($children as $c) {
                $childResult = $c->getLabeledValue($element);
                $isArrayType = $childResult->isArrayType ?? false;

                $childValues = $childResult->value ?? null;
                if ($childValues && !is_array($childValues)) {
                    $childValues = [$childValues];
                }

                $newValue = null;

                if (is_array($childValues)) {
                    foreach ($childValues as $value) {
                        if (is_array($value)) {
                            $newSubValues = [];
                            foreach ($value as $subValue) {
                                $subValue = $this->format($subValue);
                                $newSubValues[] = $subValue;
                            }
                            $newValue = $newSubValues;
                        } else {
                            $newValue = $this->format($value);
                        }
                    }
                }

                $newChildrenResult[] = $newValue;
            }

            $result->isArrayType = $isArrayType;
            if ($isArrayType) {
                $result->value = $newChildrenResult;
            } else {
                $result->value = $newChildrenResult[0];
            }
        }

        return $result;
    }

    public function format(mixed $theValue): string
    {
        $timestamp = null;
        if (is_int($theValue)) {
            $theValue = Carbon::createFromTimestamp($theValue);
        }
        if ($theValue instanceof Carbon) {
            $timestamp = $theValue->getTimestamp();
        }

        if ($timestamp && $this->format) {
            return date($this->format, $timestamp);
        }

        if ($theValue instanceof Carbon) {
            return $theValue->toDateString();
        }

        return $theValue;
    }
}
