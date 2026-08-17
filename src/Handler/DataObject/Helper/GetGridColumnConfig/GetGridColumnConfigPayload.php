<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetGridColumnConfig;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final class GetGridColumnConfigPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?int $objectId,
        public readonly ?string $name,
        public readonly ?string $type,
        public readonly ?string $types,
        public readonly ?string $gridtype,
        public readonly ?int $gridConfigId,
        public readonly ?string $searchType,
        public readonly bool $noSystemColumns,
        public readonly bool $noBrickColumns,
        public readonly string $locale,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->getString('id') ?: null,
            objectId: is_numeric($request->query->get('objectId')) ? $request->query->getInt('objectId') : null,
            name: $request->query->getString('name') ?: null,
            type: $request->query->getString('type') ?: null,
            types: $request->query->getString('types') ?: null,
            gridtype: $request->query->getString('gridtype') ?: null,
            gridConfigId: is_numeric($request->query->get('gridConfigId')) ? $request->query->getInt('gridConfigId') : null,
            searchType: $request->query->getString('searchType') ?: null,
            noSystemColumns: $request->query->getBoolean('no_system_columns'),
            noBrickColumns: $request->query->getBoolean('no_brick_columns'),
            locale: $request->getLocale(),
        );
    }
}
