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
use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider\Assets;
use OpenDxp\Controller\KernelControllerEventInterface;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class AssetController
 *
 * @package GDPRDataExtractorBundle\Controller
 *
 * @internal
 */
#[Route('/asset')]
class AssetController extends AdminAbstractController implements KernelControllerEventInterface
{
    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->checkActionPermission($event, 'gdpr_data_extractor');
    }

    #[Route('/search-assets', name: 'opendxp_admin_gdpr_asset_searchasset', methods: ['GET'])]
    public function searchAssetAction(Request $request, Assets $service): JsonResponse
    {
        $allParams = [...$request->request->all(), ...$request->query->all()];

        $result = $service->searchData(
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

    /**
     * @throws \Exception
     */
    #[Route('/export', name: 'opendxp_admin_gdpr_asset_exportassets', methods: ['GET'])]
    public function exportAssetsAction(Request $request, Assets $service): Response
    {
        $asset = Asset::getById((int) $request->get('id'));
        if (!$asset) {
            throw $this->createNotFoundException('Asset not found');
        }
        if (!$asset->isAllowed('view')) {
            throw $this->createAccessDeniedException('Export denied');
        }

        return $service->doExportData($asset);
    }
}
