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

use Exception;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider\Assets;
use OpenDxp\Bundle\AdminBundle\Security\Permission\AdminPermission;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/asset')]
#[IsGranted(AdminPermission::GdprDataExtractor->value)]
class AssetController extends AdminAbstractController
{
    #[Route('/search-assets', name: 'opendxp_admin_gdpr_asset_searchasset', methods: ['GET'])]
    public function searchAssetAction(Request $request, Assets $service): JsonResponse
    {
        $allParams = $request->query->all();

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
     * @throws Exception
     */
    #[Route('/export', name: 'opendxp_admin_gdpr_asset_exportassets', methods: ['GET'])]
    public function exportAssetsAction(
        Assets $service,
        #[MapQueryParameter] int $id = 0,
    ): Response
    {
        $asset = Asset::getById($id);
        if (!$asset) {
            throw $this->createNotFoundException('Asset not found');
        }
        if (!$asset->isAllowed('view')) {
            throw $this->createAccessDeniedException('Export denied');
        }

        return $service->doExportData($asset);
    }
}
