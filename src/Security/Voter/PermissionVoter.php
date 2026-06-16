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

namespace OpenDxp\Bundle\AdminBundle\Security\Voter;

use OpenDxp\Bundle\AdminBundle\Security\Permission\AdminPermission;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Security\User\TokenStorageUserResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PermissionVoter extends Voter
{
    public function __construct(
        private readonly TokenStorageUserResolver $tokenResolver,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return CorePermission::tryFrom($attribute) !== null || AdminPermission::tryFrom($attribute) !== null;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $this->tokenResolver->getUser();
        if (!$user) {
            return false;
        }

        return $user->isAllowed($attribute);
    }
}
