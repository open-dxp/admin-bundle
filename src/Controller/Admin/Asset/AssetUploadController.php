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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Asset;

use OpenDxp\Bundle\AdminBundle\Attribute\AsHtmlContentTypeResponse;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\AddAsset\AddAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\AddAsset\AddAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\AddAssetCompatibility\AddAssetCompatibilityHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\CheckAssetExists\CheckAssetExistsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\CheckAssetExists\CheckAssetExistsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZip\ImportZipHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZip\ImportZipPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZipFiles\ImportZipFilesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZipFiles\ImportZipFilesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ReplaceAsset\ReplaceAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ReplaceAsset\ReplaceAssetPayload;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[IsGranted(CorePermission::Assets->value)]
#[Route('/asset')]
class AssetUploadController extends AdminAbstractController
{
    #[Route('/add-asset', name: 'opendxp_admin_asset_addasset', methods: ['POST'])]
    public function addAssetAction(AddAssetPayload $payload, AddAssetHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[AsHtmlContentTypeResponse]
    #[Route('/add-asset-compatibility', name: 'opendxp_admin_asset_addassetcompatibility', methods: ['POST'])]
    public function addAssetCompatibilityAction(AddAssetPayload $payload, AddAssetCompatibilityHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[Route('/exists', name: 'opendxp_admin_asset_exists', methods: ['GET'])]
    public function existsAction(
        CheckAssetExistsPayload $payload,
        CheckAssetExistsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[AsHtmlContentTypeResponse]
    #[Route('/replace-asset', name: 'opendxp_admin_asset_replaceasset', methods: ['POST', 'PUT'])]
    public function replaceAssetAction(ReplaceAssetPayload $payload, ReplaceAssetHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[Route('/import-zip', name: 'opendxp_admin_asset_importzip', methods: ['POST'])]
    public function importZipAction(
        ImportZipPayload $payload,
        ImportZipHandler $handler,
    ): Response {
        // encodeJson(), not adminJson(): this POST isn't XHR, so a JSON content-type
        // would trigger a download dialog in most browsers instead of being read by the iframe
        return new Response(
            $this->encodeJson(['success' => true, ...get_object_vars($handler($payload))])
        );
    }

    #[Route('/import-zip-files', name: 'opendxp_admin_asset_importzipfiles', methods: ['POST'])]
    public function importZipFilesAction(
        ImportZipFilesPayload $payload,
        ImportZipFilesHandler $handler
    ): JsonResponse {

        $handler($payload);

        return $this->apiOk();
    }
}
