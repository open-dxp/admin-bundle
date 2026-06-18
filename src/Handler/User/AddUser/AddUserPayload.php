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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\AddUser;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class AddUserPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string|null $type,
        public readonly int         $parentId,
        public readonly string      $name,
        public readonly bool        $active,
        public readonly int|null    $referenceId,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $name = $request->request->get('name', '');
        $referenceId = $request->request->has('rid') ? (int) $request->request->get('rid') : null;

        return new static(
            type: $request->request->get('type'),
            parentId: $request->request->getInt('parentId'),
            name: trim((string) $name),
            active: $request->request->getBoolean('active'),
            referenceId: $referenceId,
        );
    }
}
