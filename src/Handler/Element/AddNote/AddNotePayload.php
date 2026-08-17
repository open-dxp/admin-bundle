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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\AddNote;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class AddNotePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $cid,
        public readonly string $ctype,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $type = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            cid: (int) $request->request->get('cid'),
            ctype: $request->request->get('ctype'),
            title: $request->request->get('title'),
            description: $request->request->get('description'),
            type: $request->request->get('type'),
        );
    }
}
