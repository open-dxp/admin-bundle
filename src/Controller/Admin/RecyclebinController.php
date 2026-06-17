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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\AddToRecyclebinHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\DeleteRecyclebinItemHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\ListRecyclebinHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\RecyclebinPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\RestoreRecyclebinItemHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Controller\KernelControllerEventInterface;
use OpenDxp\Model\Element;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
class RecyclebinController extends AdminAbstractController implements KernelControllerEventInterface
{
    #[IsGranted(CorePermission::Recyclebin->value)]
    #[Route('/recyclebin/list', name: 'opendxp_admin_recyclebin_list', methods: ['POST'])]
    public function listAction(
        RecyclebinPayload $payload,
        ListRecyclebinHandler $listRecyclebin,
        DeleteRecyclebinItemHandler $deleteRecyclebinItem,
        #[MapQueryParameter] ?string $xaction = null,
    ): JsonResponse {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->destroyRecyclebinItem($deleteRecyclebinItem, $payload),
                default   => throw new BadRequestHttpException(),
            };
        }

        $result = $listRecyclebin($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    private function destroyRecyclebinItem(DeleteRecyclebinItemHandler $handler, RecyclebinPayload $payload): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }

    #[IsGranted(CorePermission::Recyclebin->value)]
    #[Route('/recyclebin/restore', name: 'opendxp_admin_recyclebin_restore', methods: ['POST'])]
    public function restoreAction(
        RestoreRecyclebinItemHandler $restoreRecyclebinItem,
        Request $request,
    ): JsonResponse {
        $restoreRecyclebinItem((int) $request->request->get('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Recyclebin->value)]
    #[Route('/recyclebin/flush', name: 'opendxp_admin_recyclebin_flush', methods: ['DELETE'])]
    public function flushAction(): JsonResponse
    {
        $bin = new Element\Recyclebin();
        $bin->flush();

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/recyclebin/add', name: 'opendxp_admin_recyclebin_add', methods: ['POST'])]
    public function addAction(
        AddToRecyclebinHandler $addToRecyclebin,
        Request $request,
    ): JsonResponse {
        $addToRecyclebin(
            type: $request->request->get('type'),
            id: $request->request->getInt('id'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $timeout = 600; // 10 minutes
        @ini_set('max_execution_time', (string) $timeout);
        set_time_limit($timeout);
    }
}
