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
use OpenDxp\Bundle\AdminBundle\Handler\Settings\AddVideoThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeleteVideoThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetVideoThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetVideoThumbnailListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetVideoThumbnailTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdateVideoThumbnailHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[IsGranted(CorePermission::Thumbnails->value)]
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

    #[Route('/settings/video-thumbnail-add', name: 'opendxp_admin_settings_videothumbnailadd', methods: ['POST'])]
    public function videoThumbnailAddAction(Request $request, AddVideoThumbnailHandler $addVideoThumbnail): JsonResponse
    {
        $result = $addVideoThumbnail($request->request->get('name'));

        return $this->adminJson(ApiResponse::fromBool($result->created, ['id' => $result->id]));
    }

    #[Route('/settings/video-thumbnail-delete', name: 'opendxp_admin_settings_videothumbnaildelete', methods: ['DELETE'])]
    public function videoThumbnailDeleteAction(Request $request, DeleteVideoThumbnailHandler $deleteVideoThumbnail): JsonResponse
    {
        $deleteVideoThumbnail($request->request->get('name'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/settings/video-thumbnail-get', name: 'opendxp_admin_settings_videothumbnailget', methods: ['GET'])]
    public function videoThumbnailGetAction(
        #[MapQueryParameter] string $name,
        GetVideoThumbnailHandler $getVideoThumbnail,
    ): JsonResponse {
        $result = $getVideoThumbnail($name);

        return $this->adminJson($result->data);
    }

    #[Route('/settings/video-thumbnail-update', name: 'opendxp_admin_settings_videothumbnailupdate', methods: ['PUT'])]
    public function videoThumbnailUpdateAction(Request $request, UpdateVideoThumbnailHandler $updateVideoThumbnail): JsonResponse
    {
        $updateVideoThumbnail(
            name: $request->request->get('name'),
            settingsData: $this->decodeJson($request->request->get('settings')),
            mediaData: $this->decodeJson($request->request->get('medias')),
            mediaOrder: $this->decodeJson($request->request->get('mediaOrder')),
        );

        return $this->adminJson(ApiResponse::ok());
    }
}
