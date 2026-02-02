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

namespace OpenDxp\Bundle\AdminBundle\Controller\GDPR;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider\OpenDxpUsers;
use OpenDxp\Controller\KernelControllerEventInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class OpenDxpController
 *
 * @internal
 */
#[Route('/opendxp-users')]
class OpenDxpUsersController extends AdminAbstractController implements KernelControllerEventInterface
{
    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->checkActionPermission($event, 'gdpr_data_extractor');
    }

    #[Route('/search-users', name: 'opendxp_admin_gdpr_opendxpusers_searchusers', methods: ['GET'])]
    public function searchUsersAction(Request $request, OpenDxpUsers $openDxpUsers): JsonResponse
    {
        $allParams = $request->query->all();

        $result = $openDxpUsers->searchData(
            (int)$allParams['id'],
            strip_tags($allParams['firstname']),
            strip_tags($allParams['lastname']),
            strip_tags($allParams['email']),
            (int)$allParams['start'],
            (int)$allParams['limit'],
            $allParams['sort'] ?? null
        );

        return $this->adminJson($result);
    }

    #[Route('/export-user-data', name: 'opendxp_admin_gdpr_opendxpusers_exportuserdata', methods: ['GET'])]
    public function exportUserDataAction(Request $request, OpenDxpUsers $openDxpUsers): JsonResponse
    {
        $this->checkPermission('users');
        $userData = $openDxpUsers->getExportData((int)$request->query->get('id'));

        $json = $this->encodeJson($userData, [], JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);

        return new JsonResponse($json, 200, [
            'Content-Disposition' => 'attachment; filename="export-userdata-' . $userData['id'] . '.json"',
        ], true);
    }
}
