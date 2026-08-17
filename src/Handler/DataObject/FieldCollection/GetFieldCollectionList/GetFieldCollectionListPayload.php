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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionList;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetFieldCollectionListPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $layoutId = null,
        public readonly ?array $allowedTypes = null,
        public readonly ?string $fieldName = null,
        public readonly int $objectId = 0,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $allowedTypes = $request->query->getString('allowedTypes');

        return new static(
            layoutId:     $request->query->getString('layoutId') ?: null,
            allowedTypes: $allowedTypes !== '' ? explode(',', $allowedTypes) : null,
            fieldName:    $request->query->getString('field_name') ?: null,
            objectId:     $request->query->getInt('object_id'),
        );
    }
}
