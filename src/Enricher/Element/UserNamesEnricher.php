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

namespace OpenDxp\Bundle\AdminBundle\Enricher\Element;

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserNamesEnricher
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    public function enrich(ElementInterface $element, array &$data): void
    {
        $ownerName = $this->resolveUserName($element->getUserOwner());
        $modificationName = $element->getUserOwner() === $element->getUserModification()
            ? $ownerName
            : $this->resolveUserName($element->getUserModification());

        $data['userOwnerUsername'] = $ownerName['userName'];
        $data['userOwnerFullname'] = $ownerName['fullName'];
        $data['userModificationUsername'] = $modificationName['userName'];
        $data['userModificationFullname'] = $modificationName['fullName'];
    }

    private function resolveUserName(?int $userId): array
    {
        $unknown = ['userName' => '', 'fullName' => $this->translator->trans('user_unknown', [], 'admin')];

        if ($userId === null) {
            return $unknown;
        }

        $user = User::getById($userId);
        if (empty($user)) {
            return $unknown;
        }

        return [
            'userName' => $user->getName(),
            'fullName' => empty($user->getFullName()) ? $user->getName() : $user->getFullName(),
        ];
    }
}
