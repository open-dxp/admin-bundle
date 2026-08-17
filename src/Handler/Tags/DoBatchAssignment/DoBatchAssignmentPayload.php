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

namespace OpenDxp\Bundle\AdminBundle\Handler\Tags\DoBatchAssignment;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DoBatchAssignmentPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $elementType,
        public array $childrenIds,
        public array $assignedTags,
        public bool $doCleanupTags,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $childrenIds = json_decode($request->request->get('childrenIds'), true);
        $assignedTags = json_decode($request->request->get('assignedTags'), true);

        return new static(
            elementType: strip_tags($request->request->get('elementType', '')),
            childrenIds: is_array($childrenIds) ? $childrenIds : [],
            assignedTags: is_array($assignedTags) ? $assignedTags : [],
            doCleanupTags: $request->request->get('removeAndApply') === 'true',
        );
    }
}
