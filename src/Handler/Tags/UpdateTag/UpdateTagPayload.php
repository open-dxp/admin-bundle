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

namespace OpenDxp\Bundle\AdminBundle\Handler\Tags\UpdateTag;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UpdateTagPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $id,
        public ?int $parentId,
        public ?string $name,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $parentId = $request->request->get('parentId');

        return new static(
            id: (int) $request->request->get('id'),
            parentId: ($parentId || $parentId === '0') ? (int) $parentId : null,
            name: $request->request->has('text') ? strip_tags($request->request->get('text', '')) : null,
        );
    }
}
