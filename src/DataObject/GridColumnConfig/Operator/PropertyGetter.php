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
final class PropertyGetter extends AbstractOperator
{
    private string $propertyName;

    public function __construct(stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->propertyName = $config->propertyName ?? '';
    }

    public function getLabeledValue(array|ElementInterface $element): stdClass
    {
        $result = new stdClass();
        $result->label = $this->label;
        $result->value = $element->getProperty($this->getPropertyName());

        return $result;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }
}
