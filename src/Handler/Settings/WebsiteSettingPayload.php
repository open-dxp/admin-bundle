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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class WebsiteSettingPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly bool $hasData,
        public readonly array $data = [],
        public readonly int $limit = 50,
        public readonly int $offset = 0,
        public readonly ?string $orderKey = null,
        public readonly ?string $order = null,
        public readonly ?string $filter = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        if ($request->request->has('data')) {
            $data = json_decode($request->request->getString('data'), true) ?? [];

            if (is_array($data)) {
                foreach ($data as &$value) {
                    if (is_string($value)) {
                        $value = trim($value);
                    }
                }
                unset($value);
            }

            return new static(hasData: true, data: $data);
        }

        $sortingSettings = QueryParams::extractSortingSettings([...$request->request->all(), ...$request->query->all()]);

        return new static(
            hasData: false,
            limit: $request->request->getInt('limit', 50),
            offset: $request->request->getInt('start', 0),
            orderKey: $sortingSettings['orderKey'] ?: null,
            order: $sortingSettings['order'] ?? null,
            filter: $request->request->has('filter') ? $request->request->getString('filter') : null,
        );
    }
}
