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
use OpenDxp\Bundle\AdminBundle\Handler\GDPR\Asset\ExportAsset\ExportAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\GDPR\Asset\SearchAssets\SearchAssetsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\GDPR\SearchDataPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\AdminPermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
    public function searchAssetAction(SearchAssetsHandler $handler, SearchDataPayload $payload): JsonResponse
    {
        return $this->adminJson($handler($payload)->data);
    }

    #[Route('/export', name: 'opendxp_admin_gdpr_asset_exportassets', methods: ['GET'])]
    public function exportAssetsAction(ExportAssetHandler $handler, IdQueryPayload $payload): Response
    {
        return $handler($payload)->response;
    }
}
