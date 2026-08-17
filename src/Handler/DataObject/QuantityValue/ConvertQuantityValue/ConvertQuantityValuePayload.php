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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ConvertQuantityValue;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ConvertQuantityValuePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $fromUnitId = null,
        public readonly ?string $toUnitId = null,
        public readonly ?string $value = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            fromUnitId: $request->query->getString('fromUnit') ?: null,
            toUnitId:   $request->query->getString('toUnit') ?: null,
            value:      $request->query->getString('value') ?: null,
        );
    }
}
