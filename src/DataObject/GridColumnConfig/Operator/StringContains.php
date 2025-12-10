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
final class StringContains extends AbstractOperator
{
    private string $search;

    private bool $insensitive;

    public function __construct(stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->search = $config->search ?? '';
        $this->insensitive = $config->insensitive ?? false;
    }

    public function getLabeledValue(array|ElementInterface $element): stdClass
    {
        $result = new stdClass();
        $result->label = $this->label;
        $result->value = null;

        $children = $this->getChildren();

        if ($children) {
            $newChildrenResult = [];

            foreach ($children as $c) {
                $childResult = $c->getLabeledValue($element);
                $childValues = $childResult->value;
                if ($childValues && !is_array($childValues)) {
                    $childValues = [$childValues];
                }

                $newValue = null;

                if (is_array($childValues)) {
                    foreach ($childValues as $value) {
                        if (is_array($value)) {
                            $newSubValues = [];
                            foreach ($value as $subValue) {
                                $subValue = $this->contains($subValue);
                                $newSubValues[] = $subValue;
                            }
                            $newValue = $newSubValues;
                        } else {
                            $newValue = $this->contains($value);
                        }
                    }
                }

                $newChildrenResult[] = $newValue;
            }

            $result->value = $newChildrenResult;
        }

        return $result;
    }

    public function contains(string $value): bool
    {
        $needle = $this->getSearch();
        if (empty($needle)) {
            return false;
        }

        if ($this->getInsensitive()) {
            return stripos($value, $this->getSearch()) !== false;
        }

        return str_contains($value, $this->getSearch());
    }

    public function getSearch(): string
    {
        return $this->search;
    }

    public function setSearch(string $search): void
    {
        $this->search = $search;
    }

    public function getInsensitive(): bool
    {
        return $this->insensitive;
    }

    public function setInsensitive(bool $insensitive): void
    {
        $this->insensitive = $insensitive;
    }
}
