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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\CopyInfo\CopyInfoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\CopyInfo\CopyInfoPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\RewriteDataObjectIds\RewriteDataObjectIdsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\RewriteDataObjectIds\RewriteDataObjectIdsPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
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
        CopyInfoPayload $payload,
        CopyInfoHandler $handler,
        Request $request,
    ): JsonResponse {
        $result = $handler($payload);

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($result): void {
            $session->set((string) $result->transactionId, ['idMapping' => []]);
        }, 'opendxp_copy');

        return $this->adminJson(['pastejobs' => $result->pasteJobs]);
    }

    #[Route('/copy-rewrite-ids', name: 'copyrewriteids', methods: ['PUT'])]
    public function copyRewriteIdsAction(
        RewriteDataObjectIdsPayload $payload,
        RewriteDataObjectIdsHandler $handler,
        Request $request,
    ): JsonResponse {
        $handler($payload);

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($payload): void {
            $session->set($payload->transactionId, $payload->updatedIdStore);
        }, 'opendxp_copy');

        return $this->adminJson(ApiResponse::ok(['id' => $payload->objectId]));
    }

    #[Route('/copy', name: 'copy', methods: ['POST'])]
    public function copyAction(
        CopyDataObjectPayload $payload,
        CopyDataObjectHandler $handler,
        Request $request,
    ): JsonResponse {
        $result = $handler($payload);

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
