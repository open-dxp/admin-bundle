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

use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Version\PublishVersion\PublishVersionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Version\PublishVersion\PublishVersionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Version\ShowVersion\ShowVersionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Version\ShowVersion\ShowVersionPayload;
use OpenDxp\Security\CorePermission;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Twig\Extension\CoreExtension;

/**
 * @internal
 */
#[IsGranted(CorePermission::Assets->value)]
#[Route('/asset')]
class AssetVersionController extends AdminAbstractController
{
    #[Route('/publish-version', name: 'opendxp_admin_asset_publishversion', methods: ['POST'])]
    public function publishVersionAction(
        PublishVersionPayload $payload,
        PublishVersionHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/show-version', name: 'opendxp_admin_asset_showversion', methods: ['GET'])]
    public function showVersionAction(
        Environment $twig,
        ShowVersionPayload $payload,
        ShowVersionHandler $handler,
    ): Response {
        $result = $handler($payload);

        if ($result->isPdf) {
            return $this->render(
                '@OpenDxpAdmin/admin/asset/get_preview_pdf_open_in_new_tab.html.twig',
                [
                    'thumbnailPath' => '',
                    'assetPath' => $result->pdfPath,
                ],
            );
        }

        Tool\UserTimezone::setUserTimezone($payload->userTimezone);
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
