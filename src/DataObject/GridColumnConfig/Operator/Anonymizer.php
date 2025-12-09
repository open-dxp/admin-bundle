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
final class Anonymizer extends AbstractOperator
{
    private readonly string $mode;

    public function __construct(stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->mode = $config->mode ?? '';
    }

    public function getLabeledValue(array|ElementInterface $element): stdClass
    {
        $result = new stdClass();
        $result->label = $this->label;
        $result->isArrayType = true;

        $children = $this->getChildren();
        $resultItems = [];

        foreach ($children as $c) {
            $childResult = $c->getLabeledValue($element);
            $childValues = $childResult->value;

            if ($childValues) {
                if ($this->mode === 'md5') {
                    $childValues = md5($childValues);
                } elseif ($this->mode === 'bcrypt') {
                    $childValues = password_hash($childValues, PASSWORD_BCRYPT);
                }
                $resultItems[] = $childValues;
            } else {
                $resultItems[] = null;
            }
        }

        $result->value = count($children) === 1 ? $resultItems[0] : $resultItems;

        return $result;
    }
}
