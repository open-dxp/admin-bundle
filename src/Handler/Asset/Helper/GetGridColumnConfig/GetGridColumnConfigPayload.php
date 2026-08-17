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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetGridColumnConfig;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetGridColumnConfigPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $types = null,
        public readonly ?int $gridConfigId = null,
        public readonly ?string $searchType = null,
        public readonly bool $noSystemColumns = false,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->getString('id') ?: null,
            types: $request->query->getString('types') ?: null,
            gridConfigId: is_numeric($request->query->get('gridConfigId')) ? $request->query->getInt('gridConfigId') : null,
            searchType: $request->query->getString('searchType') ?: null,
            noSystemColumns: $request->query->getBoolean('no_system_columns'),
        );
    }
}
