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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class PredefinedPropertyPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly bool $hasData,
        public readonly array $data = [],
        public readonly ?string $filter = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        if ($request->request->has('data')) {
            return new static(
                hasData: true,
                data: json_decode($request->request->getString('data'), true) ?? [],
            );
        }

        return new static(
            hasData: false,
            filter: $request->request->has('filter') ? $request->request->getString('filter') : null,
        );
    }
}
