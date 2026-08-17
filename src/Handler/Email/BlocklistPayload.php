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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class BlocklistPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly bool $hasData,
        public readonly array $data = [],
        public readonly int $limit = 50,
        public readonly int $offset = 0,
        public readonly array $sortingSettings = [],
        public readonly ?string $filter = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        if ($request->request->has('data')) {
            $data = json_decode($request->request->getString('data'), true) ?? [];

            if (is_array($data)) {
                foreach ($data as $key => &$value) {
                    if (is_string($value)) {
                        if ($key === 'address') {
                            $value = filter_var($value, FILTER_SANITIZE_EMAIL) ?: '';
                        }
                        $value = trim($value);
                    }
                }
                unset($value);
            }

            return new static(hasData: true, data: is_array($data) ? $data : []);
        }

        return new static(
            hasData: false,
            limit: (int) $request->request->getString('limit', '50'),
            offset: (int) $request->request->getString('start', '0'),
            sortingSettings: QueryParams::extractSortingSettings($request->request->all()),
            filter: $request->request->has('filter') ? $request->request->getString('filter') : null,
        );
    }
}
