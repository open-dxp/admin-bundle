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

namespace OpenDxp\Bundle\AdminBundle\Payload\DataObject;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetSelectOptionsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $objectId = 0,
        public ?array $changedData = null,
        public array $fieldDefinitionConfig = [],
        public array $context = [],
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            objectId: $request->request->getInt('objectId'),
            changedData: $request->request->has('changedData') ? json_decode($request->request->getString('changedData'), true) : null,
            fieldDefinitionConfig: json_decode($request->request->getString('fieldDefinition'), true) ?? [],
            context: json_decode($request->request->getString('context'), true) ?? [],
        );
    }
}
