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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassTree;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetClassTreePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public bool $createAllowed = false,
        public bool $withId = false,
        public bool $useTitle = false,
        public bool $grouped = false,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            createAllowed: (bool) $request->query->get('createAllowed'),
            withId: (bool) $request->query->get('withId'),
            useTitle: (bool) $request->query->get('useTitle'),
            grouped: (bool) $request->query->get('grouped'),
        );
    }
}
