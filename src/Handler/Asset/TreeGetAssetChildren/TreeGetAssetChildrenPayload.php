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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\TreeGetAssetChildren;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class TreeGetAssetChildrenPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $nodeId = 0,
        public readonly ?string $customViewId = null,
        public readonly ?string $filter = null,
        public readonly int $limit = 100000000,
        public readonly int $offset = 0,
        public readonly bool $hasLimit = false,
        public readonly int $inSearch = 0,
        public readonly array $queryAll = [],
    ) {}

    public function hasPagination(): bool
    {
        return $this->hasLimit;
    }

    public static function fromRequest(Request $request): static
    {
        $filter = $request->query->has('filter') ? $request->query->getString('filter') : null;
        $limit = (int) $request->query->getString('limit');
        if ($filter !== null) {
            $limit = 100;
        } elseif (!$limit) {
            $limit = 100000000;
        }

        return new static(
            nodeId:       (int) $request->query->getString('node'),
            customViewId: ($request->query->getString('view') ?: null),
            filter:       $filter,
            limit:        $limit,
            offset:       (int) $request->query->getString('start'),
            hasLimit:     !empty($request->query->get('limit')),
            inSearch:     (int) $request->query->getString('inSearch'),
            queryAll:     $request->query->all(),
        );
    }
}
