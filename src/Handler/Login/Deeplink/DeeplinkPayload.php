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

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\Deeplink;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DeeplinkPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $queryString,
        public readonly string $perspective,
        public readonly ?string $deeplink = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $queryString = (string) ($request->server->get('QUERY_STRING') ?? '');
        $perspective = (string) $request->query->get('perspective', '');
        $perspective = strip_tags($perspective);

        $deeplink = null;
        if (preg_match('/(document|asset|object)_(\d+)_([a-z]+)/', $queryString, $matches)) {
            $deeplink = $matches[0];
        }

        return new static(
            queryString: $queryString,
            perspective: $perspective,
            deeplink: $deeplink,
        );
    }
}
