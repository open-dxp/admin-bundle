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

use OpenDxp\Model\Notification\Service\UserService;
use OpenDxp\Model\User;
use Symfony\Contracts\Translation\TranslatorInterface;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class GetRecipientsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(UserService $service, TranslatorInterface $translator): GetRecipientsResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $data = [];
        $group = $translator->trans('group', [], 'admin');

        foreach ($service->findAll($adminUser) as $recipient) {
            $prefix = $recipient->getType() === 'role' ? $group . ' - ' : '';

            $data[] = [
                'id' => $recipient->getId(),
                'text' => $prefix . $recipient->getName(),
            ];
        }

        return new GetRecipientsResult(data: $data);
    }
}
