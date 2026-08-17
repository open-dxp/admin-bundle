<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ConvertAllQuantityValues;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ConvertAllQuantityValuesPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $unitId = null,
        public readonly ?string $value = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            unitId: $request->query->getString('unit') ?: null,
            value:  $request->query->getString('value') ?: null,
        );
    }
}
