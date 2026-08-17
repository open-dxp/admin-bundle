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

namespace OpenDxp\Bundle\AdminBundle\Handler\Recyclebin;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class RecyclebinPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly bool $hasData,
        public readonly int $id = 0,
        public readonly int $limit = 50,
        public readonly int $offset = 0,
        public readonly string $orderKey = 'date',
        public readonly string $order = 'DESC',
        public readonly ?string $filterFullText = null,
        public readonly array $filters = [],
    ) {}

    public static function fromRequest(Request $request): static
    {
        if ($request->request->has('data')) {
            return new static(
                hasData: true,
                id: QueryParams::getRecordIdForGridRequest($request->request->getString('data')),
            );
        }

        $sortingSettings = QueryParams::extractSortingSettings($request->request->all());
        $orderKey = $sortingSettings['orderKey'] ?: 'date';
        $order = $sortingSettings['orderKey'] ? $sortingSettings['order'] : 'DESC';

        $filters = json_decode($request->request->getString('filter', '[]'), true);

        return new static(
            hasData: false,
            limit: $request->request->getInt('limit', 50),
            offset: $request->request->getInt('start', 0),
            orderKey: $orderKey,
            order: $order,
            filterFullText: $request->request->get('filterFullText') ?: null,
            filters: is_array($filters) ? $filters : [],
        );
    }
}
