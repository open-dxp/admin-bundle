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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetGroups;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetGroupsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public array $queryAll = [],
        public int $limit = 15,
        public int $start = 0,
        public ?string $dir = null,
        public ?string $sort = null,
        public bool $overrideSort = false,
        public ?string $searchfilter = null,
        public int $storeId = 0,
        public ?string $filter = null,
        public ?int $oid = null,
        public ?string $fieldname = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $rawLimit = $request->query->getInt('limit');

        return new static(
            queryAll: $request->query->all(),
            limit: $rawLimit ?: 15,
            start: $request->query->getInt('start'),
            dir: $request->query->getString('dir') ?: null,
            sort: $request->query->getString('sort') ?: null,
            overrideSort: $request->query->getBoolean('overrideSort'),
            searchfilter: $request->query->getString('searchfilter') ?: null,
            storeId: $request->query->getInt('storeId'),
            filter: $request->query->getString('filter') ?: null,
            oid: ($v = $request->query->get('oid')) !== null && is_numeric($v) ? (int) $v : null,
            fieldname: $request->query->getString('fieldname') ?: null,
        );
    }
}
