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
final class ElementCounter extends AbstractOperator
{
    private bool $countEmpty;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->countEmpty = $config->countEmpty ?? false;
    }

    public function getLabeledValue(array|ElementInterface $element): \stdClass
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();
        $count = 0;

        foreach ($children as $c) {
            $childResult = $c->getLabeledValue($element);
            $childValues = $childResult->value ?? null;

            if ($this->getCountEmpty()) {
                if (is_array($childValues)) {
                    $count += count($childValues);
                } else {
                    $count++;
                }
            } else {
                if (is_array($childValues)) {
                    foreach ($childValues as $childValue) {
                        if ($childValue) {
                            $count++;
                        }
                    }
                } elseif ($childValues) {
                    $count++;
                }
            }
        }

        $result->value = $count;

        return $result;
    }

    public function getCountEmpty(): bool
    {
        return $this->countEmpty;
    }

    public function setCountEmpty(bool $countEmpty): void
    {
        $this->countEmpty = $countEmpty;
    }
}
