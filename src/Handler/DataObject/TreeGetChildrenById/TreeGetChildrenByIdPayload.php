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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\TreeGetChildrenById;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class TreeGetChildrenByIdPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $node = 0,
        public ?string $filter = null,
        public int $start = 0,
        public int $limit = 100000000,
        public string $view = '',
        public int $fromPaging = 0,
        public array $allParams = [],
        public int $inSearch = 0,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            node: $request->query->getInt('node'),
            filter: $request->query->getString('filter') ?: null,
            start: $request->query->getInt('start'),
            limit: $request->query->getInt('limit', 100000000),
            view: $request->query->getString('view'),
            fromPaging: $request->query->getInt('fromPaging'),
            allParams: $request->query->all(),
            inSearch: $request->query->getInt('inSearch'),
        );
    }
}
