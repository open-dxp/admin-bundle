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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Asset;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Editor\LoadAssetForEditorHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Editor\SaveImageEditorHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
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
#[IsGranted(CorePermission::Assets->value)]
class AssetEditorController extends AdminAbstractController
{
    #[Route('/image-editor', name: 'opendxp_admin_asset_imageeditor', methods: ['GET'])]
    public function imageEditorAction(LoadAssetForEditorHandler $loadForEditor, #[MapQueryParameter] int $id): Response
    {
        $result = $loadForEditor($id);

        return $this->render('@OpenDxpAdmin/admin/asset/image_editor.html.twig', ['asset' => $result->asset]);
    }

    #[Route('/image-editor-save', name: 'opendxp_admin_asset_imageeditorsave', methods: ['PUT'])]
    public function imageEditorSaveAction(
        Request $request,
        SaveImageEditorHandler $saveImageEditor,
        #[MapQueryParameter] int $id,
    ): JsonResponse {
        $saveImageEditor($id, (string) $request->request->get('dataUri'));

        return $this->adminJson(ApiResponse::ok());
    }
}
