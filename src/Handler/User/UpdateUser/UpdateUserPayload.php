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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\UpdateUser;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UpdateUserPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int         $id,
        public readonly ?array      $values,
        public readonly ?array      $workspaces,
        public readonly string|null $keyBindingsJson,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $decodedValues = $request->request->has('data')
            ? json_decode((string) $request->request->get('data'), true)
            : null;
        $decodedWorkspaces = $request->request->has('workspaces')
            ? json_decode((string) $request->request->get('workspaces'), true)
            : null;
        $keyBindingsJson = $request->request->has('keyBindings')
            ? $request->request->get('keyBindings')
            : null;

        return new static(
            id: $request->request->getInt('id'),
            values: is_array($decodedValues) ? $decodedValues : null,
            workspaces: is_array($decodedWorkspaces) ? $decodedWorkspaces : null,
            keyBindingsJson: $keyBindingsJson !== null ? (string) $keyBindingsJson : null,
        );
    }
}
