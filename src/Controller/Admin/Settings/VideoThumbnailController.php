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
use OpenDxp\Bundle\AdminBundle\Handler\Settings\AddVideoThumbnail\AddVideoThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\AddVideoThumbnail\AddVideoThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeleteVideoThumbnail\DeleteVideoThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeleteVideoThumbnail\DeleteVideoThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetVideoThumbnail\GetVideoThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetVideoThumbnailList\GetVideoThumbnailListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetVideoThumbnailTree\GetVideoThumbnailTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdateVideoThumbnail\UpdateVideoThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdateVideoThumbnail\UpdateVideoThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class VideoThumbnailController extends AdminAbstractController
{
    #[Route('/settings/video-thumbnail-adapter-check', name: 'opendxp_admin_settings_videothumbnailadaptercheck', methods: ['GET'])]
    public function videoThumbnailAdapterCheckAction(TranslatorInterface $translator): Response
    {
        $content = '';

        if (!\OpenDxp\Video::isAvailable()) {
            $content = '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
                $translator->trans('php_cli_binary_and_or_ffmpeg_binary_setting_is_missing', [], 'admin') .
                '</span>';
        }

        return new Response($content);
    }

    #[IsGranted(CorePermission::Thumbnails->value)]
    #[Route('/settings/video-thumbnail-tree', name: 'opendxp_admin_settings_videothumbnailtree', methods: ['GET', 'POST'])]
    public function videoThumbnailTreeAction(GetVideoThumbnailTreeHandler $getVideoThumbnailTree): JsonResponse
    {
        $result = $getVideoThumbnailTree();

        return $this->adminJson($result->nodes);
    }

    #[Route('/settings/video-thumbnail-list', name: 'opendxp_admin_settings_videothumbnail_list', methods: ['GET'])]
    public function videoThumbnailListAction(GetVideoThumbnailListHandler $getVideoThumbnailList): JsonResponse
    {
        $result = $getVideoThumbnailList();

        return $this->adminJson($result->thumbnails);
    }

    #[IsGranted(CorePermission::Thumbnails->value)]
    #[Route('/settings/video-thumbnail-add', name: 'opendxp_admin_settings_videothumbnailadd', methods: ['POST'])]
    public function videoThumbnailAddAction(AddVideoThumbnailPayload $payload, AddVideoThumbnailHandler $addVideoThumbnail): JsonResponse
    {
        $result = $addVideoThumbnail($payload);

        return $this->adminJson(ApiResponse::fromBool($result->created, ['id' => $result->id]));
    }

    #[IsGranted(CorePermission::Thumbnails->value)]
    #[Route('/settings/video-thumbnail-delete', name: 'opendxp_admin_settings_videothumbnaildelete', methods: ['DELETE'])]
    public function videoThumbnailDeleteAction(DeleteVideoThumbnailPayload $payload, DeleteVideoThumbnailHandler $deleteVideoThumbnail): JsonResponse
    {
        $deleteVideoThumbnail($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Thumbnails->value)]
    #[Route('/settings/video-thumbnail-get', name: 'opendxp_admin_settings_videothumbnailget', methods: ['GET'])]
    public function videoThumbnailGetAction(
        #[MapQueryParameter] string $name,
        GetVideoThumbnailHandler $getVideoThumbnail,
    ): JsonResponse {
        $result = $getVideoThumbnail($name);

        return $this->adminJson($result->data);
    }

    #[IsGranted(CorePermission::Thumbnails->value)]
    #[Route('/settings/video-thumbnail-update', name: 'opendxp_admin_settings_videothumbnailupdate', methods: ['PUT'])]
    public function videoThumbnailUpdateAction(UpdateVideoThumbnailPayload $payload, UpdateVideoThumbnailHandler $updateVideoThumbnail): JsonResponse
    {
        $updateVideoThumbnail($payload);

        return $this->adminJson(ApiResponse::ok());
    }
}
