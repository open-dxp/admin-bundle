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

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification\FindLastUnreadNotifications;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class FindLastUnreadNotificationsPayload implements ExtJsPayloadInterface
{
    public function __construct(public readonly ?int $lastUpdate = null)
    {
    }

    public static function fromRequest(Request $request): static
    {
        $raw = $request->query->get('lastUpdate');

        return new static(
            lastUpdate: $raw !== null ? (int) $raw : null,
        );
    }
}
