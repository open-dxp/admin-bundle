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

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification\FindAllNotifications;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Notification\Service\NotificationService;
use OpenDxp\Model\Notification\Service\NotificationServiceFilterParser;
use Symfony\Component\HttpFoundation\Request;

final class FindAllNotificationsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly NotificationService $notificationService,
    ) {}

    public function __invoke(FindAllNotificationsPayload $payload): FindAllNotificationsResult
    {
        $filter = ['recipient' => (int) $this->userContext->getAdminUser()?->getId()];

        $syntheticRequest = new Request(request: [NotificationServiceFilterParser::KEY_FILTER => $payload->filter]);
        $parser = new NotificationServiceFilterParser($syntheticRequest);
        foreach ($parser->parse() as $key => $val) {
            $filter[$key] = $val;
        }

        $result = $this->notificationService->findAll($filter, [
            'offset' => $payload->offset,
            'limit' => $payload->limit,
        ]);

        $data = [];
        foreach ($result['data'] as $notification) {
            $data[] = $this->notificationService->format($notification);
        }

        return new FindAllNotificationsResult(data: $data, total: (int) $result['total']);
    }
}
