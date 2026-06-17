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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\CopyDataObject\CopyDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\CopyDataObject\CopyDataObjectPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\GetDataObjectChildIds\GetDataObjectChildIdsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\GetDataObjectChildIds\GetDataObjectChildIdsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\RewriteDataObjectIds\RewriteDataObjectIdsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\RewriteDataObjectIds\RewriteDataObjectIdsPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Tool\Session;
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
        GetDataObjectChildIdsPayload $getChildIdsPayload,
        GetDataObjectChildIdsHandler $getChildIds,
        Request $request,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $targetId = null,
    ): JsonResponse {
        $transactionId = time();
        $pasteJobs = [];

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($transactionId): void {
            $session->set((string) $transactionId, ['idMapping' => []]);
        }, 'opendxp_copy');

        $sourceId = $getChildIdsPayload->sourceId;

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

            $childIds = $getChildIds($getChildIdsPayload)->ids;

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
    public function copyRewriteIdsAction(
        RewriteDataObjectIdsPayload $payload,
        RewriteDataObjectIdsHandler $rewriteIds,
        Request $request,
    ): JsonResponse {
        $rewriteIds($payload);

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($payload): void {
            $session->set($payload->transactionId, $payload->updatedIdStore);
        }, 'opendxp_copy');

        return $this->adminJson(ApiResponse::ok(['id' => $payload->objectId]));
    }

    #[Route('/copy', name: 'copy', methods: ['POST'])]
    public function copyAction(
        CopyDataObjectPayload $payload,
        CopyDataObjectHandler $copyObject,
        Request $request,
    ): JsonResponse {
        $result = $copyObject($payload);

        if ($result->newObject !== null) {
            $sessionBag = $payload->sessionBag;
            $sessionBag['idMapping'][$result->sourceId] = $result->newObject->getId();

            if ($payload->saveParentId) {
                $sessionBag['parentId'] = $result->newObject->getId();
            }

            Session::getSessionBag($request->getSession(), 'opendxp_copy')->set($payload->transactionId, $sessionBag);
        }

        return $this->adminJson(ApiResponse::ok([
            'message' => $result->newObject?->getRealFullPath() ?? '',
        ]));
    }
}
