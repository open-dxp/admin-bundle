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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue;

use OpenDxp\Model\DataObject\Data\QuantityValue;
use OpenDxp\Model\DataObject\QuantityValue\Unit;
use OpenDxp\Model\DataObject\QuantityValue\UnitConversionService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ConvertAllQuantityValuesHandler
{
    public function __construct(
        private readonly UnitConversionService $conversionService,
    ) {}

    public function __invoke(?string $unitId, ?string $value): ConvertAllQuantityValuesResult
    {
        $fromUnit = Unit::getById($unitId);
        if (!$fromUnit instanceof Unit) {
            throw new BadRequestHttpException('Invalid unit ID provided');
        }

        $baseUnit = $fromUnit->getBaseunit() ?? $fromUnit;

        $units = new Unit\Listing();
        $units->setCondition('baseunit = ' . $units->quote($baseUnit->getId()) . ' AND id != ' . $units->quote($fromUnit->getId()));

        $convertedValues = [];
        foreach ($units->getUnits() as $targetUnit) {
            $convertedValue = $this->conversionService->convert(new QuantityValue($value, $fromUnit), $targetUnit);
            $convertedValues[] = [
                'unit' => $targetUnit->getAbbreviation(),
                'unitName' => $targetUnit->getLongname(),
                'value' => round($convertedValue->getValue(), 4),
            ];
        }

        return new ConvertAllQuantityValuesResult($value, $fromUnit->getAbbreviation(), $convertedValues);
    }
}
