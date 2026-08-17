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

namespace OpenDxp\Bundle\AdminBundle\Security\Voter;

use OpenDxp\Bundle\AdminBundle\Payload\OwnUserAwareInterface;
use OpenDxp\Logger;
use OpenDxp\Security\User\TokenStorageUserResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OwnUserVoter extends Voter
{
    public const string OWN_USER = 'OWN_USER';

    public function __construct(private readonly TokenStorageUserResolver $tokenResolver)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::OWN_USER && $subject instanceof OwnUserAwareInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $this->tokenResolver->getUser();

        if ($user === null || $subject->getOwnUserId() !== $user->getId()) {
            Logger::warn('prevented save current user, because ids do not match. ');

            return false;
        }

        return true;
    }
}
