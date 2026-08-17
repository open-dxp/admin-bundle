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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetIdPathPagingInfo;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetIdPathPagingInfoPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $path = null,
        public readonly int $limit = 30,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            path: $request->query->getString('path') ?: null,
            limit: $request->query->getInt('limit', 30),
        );
    }
}
