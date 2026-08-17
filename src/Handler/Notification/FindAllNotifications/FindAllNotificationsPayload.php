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

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification\FindAllNotifications;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class FindAllNotificationsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $offset = 0,
        public readonly int $limit = 40,
        public readonly array $filter = [],
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $decodedFilter = json_decode($request->request->getString('filter', '[]'), true);

        return new static(
            offset: $request->request->getInt('start'),
            limit: $request->request->getInt('limit', 40),
            filter: is_array($decodedFilter) ? $decodedFilter : [],
        );
    }
}
