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
use OpenDxp\Bundle\AdminBundle\Handler\Settings\AddThumbnail\AddThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\AddThumbnail\AddThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeleteThumbnail\DeleteThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeleteThumbnail\DeleteThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetDownloadableThumbnails\GetDownloadableThumbnailsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetThumbnailTree\GetThumbnailTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetThumbnail\GetThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetThumbnail\GetThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdateThumbnail\UpdateThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdateThumbnail\UpdateThumbnailPayload;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
class ThumbnailController extends AdminAbstractController
{
    #[IsGranted(CorePermission::Thumbnails->value)]
    #[Route('/settings/thumbnail-tree', name: 'opendxp_admin_settings_thumbnailtree', methods: ['GET', 'POST'])]
    public function thumbnailTreeAction(GetThumbnailTreeHandler $handler): JsonResponse
    {
        return $this->apiJson($handler(), rootProperty: 'nodes');
    }

    #[Route('/settings/thumbnail-downloadable', name: 'opendxp_admin_settings_thumbnaildownloadable', methods: ['GET'])]
    public function thumbnailDownloadableAction(GetDownloadableThumbnailsHandler $handler): JsonResponse
    {
        return $this->apiJson($handler(), rootProperty: 'thumbnails');
    }

    #[IsGranted(CorePermission::Thumbnails->value)]
    #[Route('/settings/thumbnail-add', name: 'opendxp_admin_settings_thumbnailadd', methods: ['POST'])]
    public function thumbnailAddAction(AddThumbnailPayload $payload, AddThumbnailHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Thumbnails->value)]
    #[Route('/settings/thumbnail-delete', name: 'opendxp_admin_settings_thumbnaildelete', methods: ['DELETE'])]
    public function thumbnailDeleteAction(DeleteThumbnailPayload $payload, DeleteThumbnailHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Thumbnails->value)]
    #[Route('/settings/thumbnail-get', name: 'opendxp_admin_settings_thumbnailget', methods: ['GET'])]
    public function thumbnailGetAction(
        GetThumbnailPayload $payload,
        GetThumbnailHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[IsGranted(CorePermission::Thumbnails->value)]
    #[Route('/settings/thumbnail-update', name: 'opendxp_admin_settings_thumbnailupdate', methods: ['PUT'])]
    public function thumbnailUpdateAction(UpdateThumbnailPayload $payload, UpdateThumbnailHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }
}
