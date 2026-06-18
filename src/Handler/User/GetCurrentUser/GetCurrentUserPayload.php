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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\GetCurrentUser;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

final readonly class GetCurrentUserPayload implements ExtJsPayloadInterface
{
    public function __construct(public readonly bool $isPasswordReset) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            isPasswordReset: (bool) \OpenDxp\Tool\Session::useBag($request->getSession(), fn (AttributeBagInterface $adminSession) => $adminSession->get('password_reset')),
        );
    }
}
