<?php

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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObject;

use OpenDxp\Bundle\AdminBundle\Attribute\AsHtmlContentTypeResponse;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\DeleteObjectBrick\DeleteObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ExportObjectBrick\ExportObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ExportObjectBrick\ExportObjectBrickPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetBrickUsages\GetBrickUsagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetBrickUsages\GetBrickUsagesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrick\GetObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrick\GetObjectBrickPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickList\GetObjectBrickListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickList\GetObjectBrickListPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickTree\GetObjectBrickTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickTree\GetObjectBrickTreePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ImportObjectBrick\ImportObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ImportObjectBrick\ImportObjectBrickPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\UpdateObjectBrick\UpdateObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\UpdateObjectBrick\UpdateObjectBrickPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\StringIdBodyPayload;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/class', name: 'opendxp_admin_dataobject_class_')]
class ObjectBrickController extends AdminAbstractController
{
    #[Route('/objectbrick-get', name: 'objectbrickget', methods: ['GET'])]
    public function objectbrickGetAction(GetObjectBrickHandler $handler, GetObjectBrickPayload $payload): JsonResponse
    {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[Route('/objectbrick-update', name: 'objectbrickupdate', methods: ['PUT', 'POST'])]
    public function objectbrickUpdateAction(UpdateObjectBrickHandler $handler, UpdateObjectBrickPayload $payload): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Objectbricks->value)]
    #[Route('/objectbrick-delete', name: 'objectbrickdelete', methods: ['DELETE'])]
    public function objectbrickDeleteAction(DeleteObjectBrickHandler $handler, StringIdBodyPayload $payload): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/objectbrick-tree', name: 'objectbricktree', methods: ['GET', 'POST'])]
    public function objectbrickTreeAction(GetObjectBrickTreeHandler $handler, GetObjectBrickTreePayload $payload): JsonResponse
    {
        $result = $handler($payload);

        if ($payload->forObjectEditor) {
            return $this->apiJson($result, envelope: false);
        }

        return $this->apiJson($result, rootProperty: 'definitions');
    }

    #[Route('/objectbrick-list', name: 'objectbricklist', methods: ['GET'])]
    public function objectbrickListAction(GetObjectBrickListHandler $handler, GetObjectBrickListPayload $payload): JsonResponse
    {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[AsHtmlContentTypeResponse]
    #[IsGranted(CorePermission::Objectbricks->value)]
    #[Route('/import-objectbrick', name: 'importobjectbrick', methods: ['POST'])]
    public function importObjectbrickAction(ImportObjectBrickHandler $handler, ImportObjectBrickPayload $payload): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Objectbricks->value)]
    #[Route('/export-objectbrick', name: 'exportobjectbrick', methods: ['GET'])]
    public function exportObjectbrickAction(ExportObjectBrickHandler $handler, ExportObjectBrickPayload $payload): Response
    {
        $result = $handler($payload);

        $response = new Response($result->json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="objectbrick_' . $result->key . '_export.json"');

        return $response;
    }

    #[IsGranted(CorePermission::Classes->value)]
    #[Route('/get-bricks-usages', name: 'getbrickusages', methods: ['GET'])]
    public function getBrickUsagesAction(GetBrickUsagesHandler $handler, GetBrickUsagesPayload $payload): Response
    {
        return $this->apiJson($handler($payload), rootProperty: 'usages');
    }
}
