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

namespace OpenDxp\Bundle\AdminBundle\Factory;

use OpenDxp\Bundle\AdminBundle\Helper\Dashboard;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class DashboardFactory
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function create(): Dashboard
    {
        $user = $this->userContext->getAdminUser() ?? throw new \RuntimeException('Admin user not available');

        return new Dashboard($user);
    }
}
