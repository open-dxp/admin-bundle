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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObject;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetDataObjectPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $id = 0,
        public ?int $layoutId = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->getInt('id'),
            layoutId: is_numeric($request->query->get('layoutId')) ? $request->query->getInt('layoutId') : null,
        );
    }
}
