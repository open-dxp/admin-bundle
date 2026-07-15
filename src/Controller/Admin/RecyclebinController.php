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
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\AddToRecyclebin\AddToRecyclebinHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\AddToRecyclebin\AddToRecyclebinPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\DeleteRecyclebinItem\DeleteRecyclebinItemHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\FlushRecyclebin\FlushRecyclebinHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\ListRecyclebin\ListRecyclebinHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\RecyclebinPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\RestoreRecyclebinItem\RestoreRecyclebinItemHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\RestoreRecyclebinItem\RestoreRecyclebinItemPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Controller\KernelControllerEventInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
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
        Request $request,
        RecyclebinPayload $payload,
        ListRecyclebinHandler $handler,
        #[MapQueryParameter] ?string $xaction = null,
    ): Response {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->forward(self::class . '::listDestroyAction', [], $request->query->all()),
                default   => throw new AdminOperationFailedException(),
            };
        }

        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Recyclebin->value)]
    #[Route('/recyclebin/list-destroy', name: 'opendxp_admin_recyclebin_list_destroy', methods: ['POST'])]
    public function listDestroyAction(
        RecyclebinPayload $payload,
        DeleteRecyclebinItemHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Recyclebin->value)]
    #[Route('/recyclebin/restore', name: 'opendxp_admin_recyclebin_restore', methods: ['POST'])]
    public function restoreAction(
        RestoreRecyclebinItemPayload $payload,
        RestoreRecyclebinItemHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Recyclebin->value)]
    #[Route('/recyclebin/flush', name: 'opendxp_admin_recyclebin_flush', methods: ['DELETE'])]
    public function flushAction(
        FlushRecyclebinHandler $handler,
    ): JsonResponse {
        $handler();

        return $this->apiOk();
    }

    #[Route('/recyclebin/add', name: 'opendxp_admin_recyclebin_add', methods: ['POST'])]
    public function addAction(
        AddToRecyclebinPayload $payload,
        AddToRecyclebinHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
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
