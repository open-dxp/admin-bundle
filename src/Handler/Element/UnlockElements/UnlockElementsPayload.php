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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElements;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UnlockElementsPayload implements ExtJsPayloadInterface
{
    /** @param array<array{id: int|string, type: string}> $elements */
    public function __construct(public readonly array $elements)
    {
    }

    public static function fromRequest(Request $request): static
    {
        $body = json_decode($request->getContent(), true) ?? [];

        return new static(
            elements: $body['elements'] ?? [],
        );
    }
}
