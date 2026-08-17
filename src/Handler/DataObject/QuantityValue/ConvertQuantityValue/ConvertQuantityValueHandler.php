<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ConvertQuantityValue;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ConvertQuantityValue\ConvertQuantityValuePayload;
use OpenDxp\Model\DataObject\Data\QuantityValue;
use OpenDxp\Model\DataObject\QuantityValue\Unit;
use OpenDxp\Model\DataObject\QuantityValue\UnitConversionService;

final class ConvertQuantityValueHandler
{
    public function __construct(
        private readonly UnitConversionService $conversionService,
    ) {}

    public function __invoke(ConvertQuantityValuePayload $payload): ConvertQuantityValueResult
    {
        $fromUnit = Unit::getById($payload->fromUnitId);
        $toUnit = Unit::getById($payload->toUnitId);

        if (!$fromUnit instanceof Unit || !$toUnit instanceof Unit) {
            throw new AdminOperationFailedException('Invalid unit IDs provided');
        }

        $convertedValue = $this->conversionService->convert(new QuantityValue($payload->value, $fromUnit), $toUnit);

        return new ConvertQuantityValueResult($convertedValue->getValue());
    }
}
