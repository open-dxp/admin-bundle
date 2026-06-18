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

namespace OpenDxp\Bundle\AdminBundle\Handler\Notification\GetRecipients;

use OpenDxp\Bundle\AdminBundle\Payload\Common\EmptyPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Notification\Service\UserService;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GetRecipientsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly UserService $userService,
        private readonly TranslatorInterface $translator,
    ) {}

    public function __invoke(EmptyPayload $payload): GetRecipientsResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $data = [];
        $group = $this->translator->trans('group', [], 'admin');

        foreach ($this->userService->findAll($adminUser) as $recipient) {
            $prefix = $recipient->getType() === 'role' ? $group . ' - ' : '';

            $data[] = [
                'id' => $recipient->getId(),
                'text' => $prefix . $recipient->getName(),
            ];
        }

        return new GetRecipientsResult(data: $data);
    }
}
