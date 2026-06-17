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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionTree;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetFieldCollectionTreePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly bool $forObjectEditor = false,
        public readonly ?array $allowedTypes = null,
        public readonly int $objectId = 0,
        public readonly ?string $layoutId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $allowedTypes = $request->query->getString('allowedTypes');

        return new static(
            forObjectEditor: (bool) $request->query->getString('forObjectEditor'),
            allowedTypes:    $allowedTypes !== '' ? explode(',', $allowedTypes) : null,
            objectId:        $request->query->getInt('object_id'),
            layoutId:        $request->query->getString('layoutId') ?: null,
        );
    }
}
