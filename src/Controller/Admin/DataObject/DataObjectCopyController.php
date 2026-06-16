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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObject;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\CopyDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\GetDataObjectChildIdsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\RewriteDataObjectIdsHandler;
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
#[Route('/object', name: 'opendxp_admin_dataobject_dataobject_')]
#[IsGranted(CorePermission::Objects->value)]
class DataObjectCopyController extends AdminAbstractController
{
    #[Route('/copy-info', name: 'copyinfo', methods: ['GET'])]
    public function copyInfoAction(
        GetDataObjectChildIdsHandler $getChildIds,
        Request $request,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] int $sourceId = 0,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $targetId = null,
    ): JsonResponse {
        $transactionId = time();
        $pasteJobs = [];

        Tool\Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($transactionId): void {
            $session->set((string) $transactionId, ['idMapping' => []]);
        }, 'opendxp_copy');

        if ($type === 'recursive' || $type === 'recursive-update-references') {
            $pasteJobs[] = [[
                'url' => $this->generateUrl('opendxp_admin_dataobject_dataobject_copy'),
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
                    'url' => $this->generateUrl('opendxp_admin_dataobject_dataobject_copy'),
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

            if ($type === 'recursive-update-references' && count($childIds) > 0) {
                for ($i = 0; $i < (count($childIds) + 1); $i++) {
                    $pasteJobs[] = [[
                        'url' => $this->generateUrl('opendxp_admin_dataobject_dataobject_copyrewriteids'),
                        'method' => 'PUT',
                        'params' => [
                            'transactionId' => $transactionId,
                            '_dc' => uniqid('', false),
                        ],
                    ]];
                }
            }
        } elseif ($type === 'child' || $type === 'replace') {
            $pasteJobs[] = [[
                'url' => $this->generateUrl('opendxp_admin_dataobject_dataobject_copy'),
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

    #[Route('/copy-rewrite-ids', name: 'copyrewriteids', methods: ['PUT'])]
    public function copyRewriteIdsAction(RewriteDataObjectIdsHandler $rewriteIds, Request $request): JsonResponse
    {
        $transactionId = $request->request->get('transactionId');

        $idStore = Tool\Session::useBag($request->getSession(), static fn (AttributeBagInterface $session) => $session->get($transactionId), 'opendxp_copy');

        if (!array_key_exists('rewrite-stack', $idStore)) {
            $idStore['rewrite-stack'] = array_values($idStore['idMapping']);
        }

        $id = array_shift($idStore['rewrite-stack']);

        $rewriteIds((int) $id, $idStore['idMapping']);

        Tool\Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($transactionId, $idStore): void {
            $session->set($transactionId, $idStore);
        }, 'opendxp_copy');

        return $this->adminJson(ApiResponse::ok(['id' => $id]));
    }

    #[Route('/copy', name: 'copy', methods: ['POST'])]
    public function copyAction(CopyDataObjectHandler $copyObject, Request $request): JsonResponse
    {
        $sourceId = $request->request->getInt('sourceId');
        $targetId = $request->request->getInt('targetId');
        $type = (string) $request->request->get('type');

        $session = Tool\Session::getSessionBag($request->getSession(), 'opendxp_copy');
        $sessionBag = $session->get($request->request->get('transactionId'));

        $sourceParentId = $request->request->has('targetParentId') ? $request->request->getInt('sourceParentId') : null;
        $targetParentId = $request->request->has('targetParentId') ? $request->request->getInt('targetParentId') : null;
        $sessionParentId = !empty($sessionBag['parentId']) ? (int) $sessionBag['parentId'] : null;

        $result = $copyObject($sourceId, $targetId, $type, $sourceParentId, $targetParentId, $sessionParentId);

        if ($result->newObject !== null) {
            $sessionBag['idMapping'][$result->sourceId] = $result->newObject->getId();

            if ($request->request->get('saveParentId')) {
                $sessionBag['parentId'] = $result->newObject->getId();
            }

            $session->set($request->request->get('transactionId'), $sessionBag);
        }

        return $this->adminJson(ApiResponse::ok([
            'message' => $result->newObject?->getRealFullPath() ?? '',
        ]));
    }
}
