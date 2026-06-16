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
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy\CopyAssetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy\GetAssetChildIdsHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
        GetAssetChildIdsHandler $getChildIds,
        Request $request,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] int $sourceId = 0,
        #[MapQueryParameter] ?string $targetId = null,
    ): JsonResponse {
        $transactionId = time();
        $pasteJobs = [];

        Tool\Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($transactionId): void {
            $session->set((string) $transactionId, []);
        }, 'opendxp_copy');

        if ($type === 'recursive') {
            $pasteJobs[] = [[
                'url' => $this->generateUrl('opendxp_admin_asset_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $sourceId,
                    'targetId' => $targetId,
                    'type' => 'child',
                    'transactionId' => $transactionId,
                    'saveParentId' => true,
                ],
            ]];

            $childIds = $getChildIds($sourceId)->ids;
            foreach ($childIds as $id) {
                $pasteJobs[] = [[
                    'url' => $this->generateUrl('opendxp_admin_asset_copy'),
                    'method' => 'POST',
                    'params' => [
                        'sourceId' => $id,
                        'targetParentId' => $targetId,
                        'sourceParentId' => $sourceId,
                        'type' => 'child',
                        'transactionId' => $transactionId,
                    ],
                ]];
            }
        } elseif ($type === 'child' || $type === 'replace') {
            $pasteJobs[] = [[
                'url' => $this->generateUrl('opendxp_admin_asset_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $sourceId,
                    'targetId' => $targetId,
                    'type' => $type,
                    'transactionId' => $transactionId,
                ],
            ]];
        }

        return $this->adminJson(['pastejobs' => $pasteJobs]);
    }

    #[Route('/copy', name: 'opendxp_admin_asset_copy', methods: ['POST'])]
    public function copyAction(CopyAssetHandler $copyAsset, Request $request): JsonResponse
    {
        $sourceId = (int) $request->request->get('sourceId');
        $targetId = (int) $request->request->get('targetId');
        $type = (string) $request->request->get('type');

        $session = Tool\Session::getSessionBag($request->getSession(), 'opendxp_copy');
        $sessionBag = $session->get($request->request->get('transactionId'));

        $sourceParentId = $request->request->has('targetParentId') ? (int) $request->request->get('sourceParentId') : null;
        $targetParentId = $request->request->has('targetParentId') ? (int) $request->request->get('targetParentId') : null;
        $sessionParentId = $sessionBag['parentId'] ? (int) $sessionBag['parentId'] : null;

        $result = $copyAsset($sourceId, $targetId, $type, $sourceParentId, $targetParentId, $sessionParentId);

        if ($result->newAsset !== null && $request->request->get('saveParentId')) {
            $sessionBag['parentId'] = $result->newAsset->getId();
            $session->set($request->request->get('transactionId'), $sessionBag);
        }

        return $this->adminJson(ApiResponse::ok());
    }
}
