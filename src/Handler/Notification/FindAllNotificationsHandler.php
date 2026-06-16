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

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Notification\Service\NotificationService;
use OpenDxp\Model\Notification\Service\NotificationServiceFilterParser;
use Symfony\Component\HttpFoundation\Request;

final class FindAllNotificationsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(
        NotificationService $service,
        Request $request,
        int $offset,
        int $limit,
    ): FindAllNotificationsResult {
        $filter = ['recipient' => (int) $this->userContext->getAdminUser()?->getId()];

        $parser = new NotificationServiceFilterParser($request);
        foreach ($parser->parse() as $key => $val) {
            $filter[$key] = $val;
        }

        $options = [
            'offset' => $offset,
            'limit' => $limit,
        ];

        $result = $service->findAll($filter, $options);

        $data = [];
        foreach ($result['data'] as $notification) {
            $data[] = $service->format($notification);
        }

        return new FindAllNotificationsResult(data: $data, total: (int) $result['total']);
    }
}
