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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DeleteGridColumnConfig;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final class DeleteGridColumnConfigPayload implements ExtJsPayloadInterface
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
            id: $request->request->getString('id') ?: null,
            objectId: is_numeric($request->request->get('objectId')) ? $request->request->getInt('objectId') : null,
            name: $request->request->getString('name') ?: null,
            type: $request->request->getString('type') ?: null,
            types: $request->request->getString('types') ?: null,
            gridtype: $request->request->getString('gridtype') ?: null,
            gridConfigId: is_numeric($request->request->get('gridConfigId')) ? $request->request->getInt('gridConfigId') : null,
            searchType: $request->request->getString('searchType') ?: null,
            noSystemColumns: $request->query->getBoolean('no_system_columns'),
            noBrickColumns: $request->query->getBoolean('no_brick_columns'),
            locale: $request->getLocale(),
        );
    }
}
