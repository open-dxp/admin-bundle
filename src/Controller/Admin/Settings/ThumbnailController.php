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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Settings;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\AddThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeleteThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetDownloadableThumbnailsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetThumbnailTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdateThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[IsGranted(CorePermission::Thumbnails->value)]
class ThumbnailController extends AdminAbstractController
{
    #[Route('/settings/thumbnail-tree', name: 'opendxp_admin_settings_thumbnailtree', methods: ['GET', 'POST'])]
    public function thumbnailTreeAction(GetThumbnailTreeHandler $getThumbnailTree): JsonResponse
    {
        $result = $getThumbnailTree();

        return $this->adminJson($result->nodes);
    }

    #[Route('/settings/thumbnail-downloadable', name: 'opendxp_admin_settings_thumbnaildownloadable', methods: ['GET'])]
    public function thumbnailDownloadableAction(GetDownloadableThumbnailsHandler $getDownloadableThumbnails): JsonResponse
    {
        $result = $getDownloadableThumbnails();

        return $this->adminJson($result->thumbnails);
    }

    #[Route('/settings/thumbnail-add', name: 'opendxp_admin_settings_thumbnailadd', methods: ['POST'])]
    public function thumbnailAddAction(Request $request, AddThumbnailHandler $addThumbnail): JsonResponse
    {
        $result = $addThumbnail($request->request->get('name'));

        return $this->adminJson(ApiResponse::fromBool($result->created, ['id' => $result->id]));
    }

    #[Route('/settings/thumbnail-delete', name: 'opendxp_admin_settings_thumbnaildelete', methods: ['DELETE'])]
    public function thumbnailDeleteAction(Request $request, DeleteThumbnailHandler $deleteThumbnail): JsonResponse
    {
        $deleteThumbnail($request->request->get('name'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/settings/thumbnail-get', name: 'opendxp_admin_settings_thumbnailget', methods: ['GET'])]
    public function thumbnailGetAction(
        #[MapQueryParameter] string $name,
        GetThumbnailHandler $getThumbnail,
    ): JsonResponse {
        $result = $getThumbnail($name);

        return $this->adminJson($result->data);
    }

    #[Route('/settings/thumbnail-update', name: 'opendxp_admin_settings_thumbnailupdate', methods: ['PUT'])]
    public function thumbnailUpdateAction(Request $request, UpdateThumbnailHandler $updateThumbnail): JsonResponse
    {
        $updateThumbnail(
            name: $request->request->get('name'),
            settingsData: $this->decodeJson($request->request->get('settings')),
            mediaData: $this->decodeJson($request->request->get('medias')),
            mediaOrder: $this->decodeJson($request->request->get('mediaOrder')),
        );

        return $this->adminJson(ApiResponse::ok());
    }
}
