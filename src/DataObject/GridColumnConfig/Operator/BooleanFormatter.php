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
final class BooleanFormatter extends AbstractOperator
{
    private string $yesValue;

    private string $noValue;

    public function __construct(stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->yesValue = $config->yesValue ?? '';
        $this->noValue = $config->noValue ?? '';
    }

    public function getLabeledValue(array|ElementInterface $element): stdClass
    {
        $result = new stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        $booleanResult = null;

        foreach ($children as $c) {
            $childResult = $c->getLabeledValue($element);
            $childValues = $childResult->value;
            if ($childValues && !is_array($childValues)) {
                $childValues = [$childValues];
            }

            if (is_array($childValues)) {
                foreach ($childValues as $value) {
                    $value = (bool) $value;
                    $booleanResult = is_null($booleanResult) ? $value : $booleanResult && $value;
                }
            } else {
                $booleanResult = false;
            }
        }

        $booleanResult = $booleanResult ? $this->getYesValue() : $this->getNoValue();
        $result->value = $booleanResult;

        return $result;
    }

    public function getYesValue(): mixed
    {
        return $this->yesValue;
    }

    public function setYesValue(mixed $yesValue): void
    {
        $this->yesValue = $yesValue;
    }

    public function getNoValue(): mixed
    {
        return $this->noValue;
    }

    public function setNoValue(mixed $noValue): void
    {
        $this->noValue = $noValue;
    }
}
