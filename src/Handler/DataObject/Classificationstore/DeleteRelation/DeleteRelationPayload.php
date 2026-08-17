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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteRelation;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DeleteRelationPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $keyId = 0,
        public int $groupId = 0,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            keyId: $request->request->getInt('keyId'),
            groupId: $request->request->getInt('groupId'),
        );
    }
}
