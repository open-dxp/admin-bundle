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

use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Version\PublishVersionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Version\ShowVersionHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Twig\Extension\CoreExtension;

/**
 * @internal
 */
#[Route('/asset')]
#[IsGranted(CorePermission::Assets->value)]
class AssetVersionController extends AdminAbstractController
{
    #[Route('/publish-version', name: 'opendxp_admin_asset_publishversion', methods: ['POST'])]
    public function publishVersionAction(
        Request $request,
        PublishVersionHandler $publishVersion,
        ElementServiceInterface $elementService,
    ): JsonResponse {

        $result = $publishVersion(
            (int) $request->request->get('id'),
            );

        return $this->adminJson(ApiResponse::ok([
            'treeData' => $elementService->getElementTreeNodeConfig($result->asset),
        ]));
    }

    #[Route('/show-version', name: 'opendxp_admin_asset_showversion', methods: ['GET'])]
    public function showVersionAction(
        Environment $twig,
        ShowVersionHandler $showVersion,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $userTimezone = null,
    ): Response {
        $result = $showVersion($id);

        if ($result->isPdf) {
            return $this->render(
                '@OpenDxpAdmin/admin/asset/get_preview_pdf_open_in_new_tab.html.twig',
                [
                    'thumbnailPath' => '',
                    'assetPath' => $result->pdfPath
                ],
            );
        }

        Tool\UserTimezone::setUserTimezone($userTimezone);
        if ($timezone = Tool\UserTimezone::getUserTimezone()) {
            $twig->getExtension(CoreExtension::class)->setTimezone($timezone);
        }

        $loader = OpenDxp::getContainer()->get('opendxp.implementation_loader.asset.metadata.data');

        return $this->render(
            '@OpenDxpAdmin/admin/asset/show_version_' . strtolower($result->asset->getType()) . '.html.twig',
            [
                'asset'   => $result->asset,
                'version' => $result->version,
                'loader'  => $loader,
            ],
        );
    }
}
