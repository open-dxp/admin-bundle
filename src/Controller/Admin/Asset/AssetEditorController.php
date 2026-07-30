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
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Editor\LoadAssetForEditor\LoadAssetForEditorHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Editor\LoadAssetForEditor\LoadAssetForEditorPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Editor\SaveImageEditor\SaveImageEditorHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Editor\SaveImageEditor\SaveImageEditorPayload;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
    public function imageEditorAction(LoadAssetForEditorPayload $payload, LoadAssetForEditorHandler $handler): Response
    {
        $result = $handler($payload);

        return $this->render('@OpenDxpAdmin/admin/asset/image_editor.html.twig', ['asset' => $result->asset]);
    }

    #[Route('/image-editor-save', name: 'opendxp_admin_asset_imageeditorsave', methods: ['PUT'])]
    public function imageEditorSaveAction(
        SaveImageEditorPayload $payload,
        SaveImageEditorHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }
}
