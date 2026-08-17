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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\ReplaceAssignments;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ReplaceAssignmentsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $type,
        public readonly int $id,
        public readonly string $sourceType,
        public readonly int $sourceId,
        public readonly string $targetType,
        public readonly int $targetId,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            type: $request->request->get('type'),
            id: $request->request->getInt('id'),
            sourceType: $request->request->get('sourceType'),
            sourceId: $request->request->getInt('sourceId'),
            targetType: $request->request->get('targetType'),
            targetId: $request->request->getInt('targetId'),
        );
    }
}
