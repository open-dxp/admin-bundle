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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\GetQuantityValueUnits;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetQuantityValueUnitsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly array $queryAll = [],
        public readonly int $limit = 25,
        public readonly int $start = 0,
        public readonly ?string $filter = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            queryAll: $request->query->all(),
            limit:    $request->query->getInt('limit', 25),
            start:    $request->query->getInt('start', 0),
            filter:   $request->query->getString('filter') ?: null,
        );
    }
}
