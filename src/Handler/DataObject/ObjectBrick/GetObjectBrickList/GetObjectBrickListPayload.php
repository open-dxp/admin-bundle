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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickList;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetObjectBrickListPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $classId = null,
        public readonly ?string $fieldName = null,
        public readonly ?string $layoutId = null,
        public readonly int $objectId = 0,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            classId:   $request->query->getString('class_id') ?: null,
            fieldName: $request->query->getString('field_name') ?: null,
            layoutId:  $request->query->getString('layoutId') ?: null,
            objectId:  $request->query->getInt('object_id'),
        );
    }
}
