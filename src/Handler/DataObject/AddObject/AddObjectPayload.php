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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\AddObject;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class AddObjectPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $className = '',
        public string $classId = '',
        public int $parentId = 0,
        public string $key = '',
        public string $objectType = '',
        public bool $variantViaTree = false,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            className: $request->request->getString('className'),
            classId: $request->request->getString('classId'),
            parentId: $request->request->getInt('parentId'),
            key: $request->request->getString('key'),
            objectType: $request->request->getString('objecttype'),
            variantViaTree: (bool) $request->request->get('variantViaTree'),
        );
    }
}
