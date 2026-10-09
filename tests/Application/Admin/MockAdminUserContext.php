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

namespace OpenDxp\Bundle\AdminBundle\Tests\Application\Admin;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\User;
use OpenDxp\Security\User\User as UserProxy;

/**
 * Has no admin user logged in.
 */
final class MockAdminUserContext implements AdminUserContextInterface
{
    public function getAdminUser(): ?User
    {
        return null;
    }

    public function getAdminUserProxy(): ?UserProxy
    {
        return null;
    }
}
