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
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy\CopyAsset\CopyAssetPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy\CopyAsset\CopyAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy\CopyInfo\CopyInfoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy\CopyInfo\CopyInfoPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/asset')]
#[IsGranted(CorePermission::Assets->value)]
class AssetCopyController extends AdminAbstractController
{
    #[Route('/copy-info', name: 'opendxp_admin_asset_copyinfo', methods: ['GET'])]
    public function copyInfoAction(
        CopyInfoPayload $payload,
        CopyInfoHandler $handler,
        Request $request,
    ): JsonResponse {
        $result = $handler($payload);

        Tool\Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($result): void {
            $session->set((string) $result->transactionId, []);
        }, 'opendxp_copy');

        return $this->adminJson(['pastejobs' => $result->pasteJobs]);
    }

    #[Route('/copy', name: 'opendxp_admin_asset_copy', methods: ['POST'])]
    public function copyAction(
        CopyAssetPayload $payload,
        CopyAssetHandler $handler,
        Request $request,
    ): JsonResponse {
        $result = $handler($payload);

        if ($result->newAsset !== null && $payload->saveParentId) {
            $session = Tool\Session::getSessionBag($request->getSession(), 'opendxp_copy');
            $sessionBag = $session->get($payload->transactionId);
            $sessionBag['parentId'] = $result->newAsset->getId();
            $session->set($payload->transactionId, $sessionBag);
        }

        return $this->adminJson(ApiResponse::ok());
    }
}
