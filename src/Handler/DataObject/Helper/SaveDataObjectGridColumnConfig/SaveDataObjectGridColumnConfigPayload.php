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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\SaveDataObjectGridColumnConfig;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SaveDataObjectGridColumnConfigPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $objectId = 0,
        public ?string $classId = null,
        public ?string $context = null,
        public ?string $searchType = null,
        public array $gridConfigData = [],
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $meta = $request->request->getString('settings');

        return new static(
            objectId: $request->request->getInt('id'),
            classId: $request->request->getString('class_id') ?: null,
            context: $request->request->getString('context') ?: null,
            searchType: $request->request->getString('searchType') ?: null,
            gridConfigData: json_decode($request->request->getString('gridconfig'), true) ?? [],
            metadata: $meta ? json_decode($meta, true) : null,
        );
    }
}
