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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\GDPR;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider\Manager;
use OpenDxp\Controller\KernelControllerEventInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
class AdminController extends AdminAbstractController implements KernelControllerEventInterface
{
    #[Route('/get-data-providers', name: 'opendxp_admin_gdpr_admin_getdataproviders', methods: ['GET'])]
    public function getDataProvidersAction(Manager $manager): JsonResponse
    {
        $response = [];
        foreach ($manager->getServices() as $service) {
            $response[] = [
                'name' => $service->getName(),
                'jsClass' => $service->getJsClassName(),
            ];
        }

        return $this->adminJson($response);
    }

    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->checkActionPermission($event, 'gdpr_data_extractor');
    }
}
