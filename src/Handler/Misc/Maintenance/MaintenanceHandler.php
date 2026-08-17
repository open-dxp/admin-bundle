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

namespace OpenDxp\Bundle\AdminBundle\Handler\Misc\Maintenance;

use OpenDxp\Bundle\AdminBundle\Session\SessionIdentityInterface;
use OpenDxp\Tool\MaintenanceModeHelperInterface;

final class MaintenanceHandler
{
    public function __construct(
        private readonly MaintenanceModeHelperInterface $maintenanceModeHelper,
        private readonly SessionIdentityInterface $sessionIdentity,
    ) {
    }

    public function __invoke(MaintenancePayload $payload): void
    {
        if ($payload->activate) {
            $this->maintenanceModeHelper->activate($this->sessionIdentity->getId());
        }

        if ($payload->deactivate) {
            $this->maintenanceModeHelper->deactivate();
        }
    }
}
