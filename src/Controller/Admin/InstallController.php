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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Install\CheckSystem\CheckSystemHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Install\CheckSystem\CheckSystemPayload;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/install')]
class InstallController extends AdminAbstractController
{
    #[Route('/check', name: 'opendxp_admin_install_check', methods: ['GET', 'POST'])]
    public function checkAction(
        CheckSystemPayload $payload,
        CheckSystemHandler $handler,
        ?Profiler $profiler,
    ): Response {
        if ($profiler) {
            $profiler->disable();
        }

        $result = $handler($payload);

        return $this->render('@OpenDxpAdmin/admin/install/check.html.twig', $result->viewParams);
    }
}
