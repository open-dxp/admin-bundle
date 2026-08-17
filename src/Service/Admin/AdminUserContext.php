<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Service\Admin;

use OpenDxp\Model\User;
use OpenDxp\Security\User\TokenStorageUserResolver;
use OpenDxp\Security\User\User as UserProxy;

final class AdminUserContext implements AdminUserContextInterface
{
    public function __construct(private readonly TokenStorageUserResolver $tokenResolver)
    {
    }

    public function getAdminUser(): ?User
    {
        return $this->tokenResolver->getUser();
    }

    public function getAdminUserProxy(): ?UserProxy
    {
        return $this->tokenResolver->getUserProxy();
    }
}
