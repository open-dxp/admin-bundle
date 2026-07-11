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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\UpdateCurrentUser;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

final readonly class UpdateCurrentUserPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $requestedUserId,
        public readonly array $values,
        public readonly bool $isPasswordReset,
        public readonly ?string $keyBindingsJson,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $data = $request->request->get('data');
        $decodedValues = json_decode($data !== null ? (string) $data : '', true);
        $keyBindingsJson = $request->request->has('keyBindings') ? $request->request->get('keyBindings') : null;

        return new static(
            requestedUserId: (int) $request->request->get('id'),
            values: is_array($decodedValues) ? $decodedValues : [],
            isPasswordReset: (bool) \OpenDxp\Tool\Session::useBag($request->getSession(), static fn (AttributeBagInterface $adminSession) => (bool) $adminSession->get('password_reset')),
            keyBindingsJson: $keyBindingsJson !== null ? (string) $keyBindingsJson : null,
        );
    }
}
