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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\FindUsages;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class FindUsagesPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $type = null,
        public readonly ?string $path = null,
        public readonly int $limit = 50,
        public readonly int $start = 0,
        public readonly ?string $sort = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->getInt('id') ?: null,
            type: $request->query->get('type'),
            path: $request->query->get('path'),
            limit: $request->query->getInt('limit', 50),
            start: $request->query->getInt('start', 0),
            sort: $request->query->get('sort'),
        );
    }
}
