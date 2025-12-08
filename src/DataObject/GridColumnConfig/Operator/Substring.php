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

use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\ResultContainer;
use OpenDxp\Model\Element\ElementInterface;

/**
 * @internal
 */
final class Substring extends AbstractOperator
{
    private int $start;

    private int $length;

    private bool $ellipses;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->start = $config->start ?? 0;
        $this->length = $config->length ?? 0;
        $this->ellipses = $config->ellipses ?? false;
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
        $isArrayType = $childResult->isArrayType ?? false;
        $childValues = $childResult->value ?? null;
        if ($childValues && !$isArrayType) {
            $childValues = [$childValues];
        }

        if (is_array($childValues)) {
            /** @var string $childValue */
            foreach ($childValues as $childValue) {
                $showEllipses = false;
                if ($childValue && $this->getEllipses()) {
                    $start = $this->getStart() ?: 0;
                    $length = $this->getLength() ?: 0;
                    if (strlen($childValue) > ($start + $length)) {
                        $showEllipses = true;
                    }
                }

                $childValue = substr($childValue, $this->getStart(), $this->getLength());
                if ($showEllipses) {
                    $childValue .= '...';
                }

                $valueArray[] = $childValue;
            }
        } else {
            $valueArray[] = $childResult->value;
        }

        $result->isArrayType = $isArrayType;
        if ($isArrayType) {
            $result->value = $valueArray;
        } else {
            $result->value = $valueArray[0];
        }

        return $result;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function setStart(int $start): void
    {
        $this->start = $start;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): void
    {
        $this->length = $length;
    }

    public function getEllipses(): bool
    {
        return $this->ellipses;
    }

    public function setEllipses(bool $ellipses): void
    {
        $this->ellipses = $ellipses;
    }
}
