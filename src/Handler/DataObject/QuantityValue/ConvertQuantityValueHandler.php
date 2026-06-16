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

final class ConvertQuantityValueHandler
{
    public function __construct(
        private readonly UnitConversionService $conversionService,
    ) {}

    public function __invoke(?string $fromUnitId, ?string $toUnitId, ?string $value): ConvertQuantityValueResult
    {
        $fromUnit = Unit::getById($fromUnitId);
        $toUnit = Unit::getById($toUnitId);

        if (!$fromUnit instanceof Unit || !$toUnit instanceof Unit) {
            throw new BadRequestHttpException('Invalid unit IDs provided');
        }

        $convertedValue = $this->conversionService->convert(new QuantityValue($value, $fromUnit), $toUnit);

        return new ConvertQuantityValueResult($convertedValue->getValue());
    }
}
