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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetPage;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetPagePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public ?string $table = null,
        public int $id = 0,
        public int $storeId = 0,
        public int $pageSize = 0,
        public ?string $sortKey = null,
        public ?string $sortDir = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            table: $request->query->getString('table') ?: null,
            id: $request->query->getInt('id'),
            storeId: $request->query->getInt('storeId'),
            pageSize: $request->query->getInt('pageSize'),
            sortKey: $request->query->getString('sortKey') ?: null,
            sortDir: $request->query->getString('sortDir') ?: null,
        );
    }
}
