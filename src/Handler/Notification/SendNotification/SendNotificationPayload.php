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

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification\SendNotification;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SendNotificationPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $recipientId = 0,
        public readonly string $title = '',
        public readonly string $message = '',
        public readonly int $elementId = 0,
        public readonly ?string $elementType = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            recipientId: (int) $request->request->getString('recipientId'),
            title: $request->request->getString('title'),
            message: $request->request->getString('message'),
            elementId: (int) $request->request->getString('elementId'),
            elementType: $request->request->has('elementType')
                ? $request->request->getString('elementType')
                : null,
        );
    }
}
