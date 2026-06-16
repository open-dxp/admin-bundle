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

use Doctrine\DBAL\Connection;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Tool\Requirements;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

/**
 * @internal
 */
#[Route('/install')]
class InstallController extends AdminAbstractController
{
    #[Route('/check', name: 'opendxp_admin_install_check', methods: ['GET', 'POST'])]
    public function checkAction(
        Request $request,
        Connection $db,
        ?Profiler $profiler,
        #[MapQueryParameter] bool $headless = false,
    ): Response
    {
        if ($profiler) {
            $profiler->disable();
        }

        $viewParams = Requirements::checkAll($db);
        $viewParams['headless'] = $headless || $request->request->getBoolean('headless');

        return $this->render('@OpenDxpAdmin/admin/install/check.html.twig', $viewParams);
    }
}
