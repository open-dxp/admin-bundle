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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObject;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\DeleteObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ExportObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ExportObjectBrick\ExportObjectBrickPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetBrickUsagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetBrickUsages\GetBrickUsagesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrick\GetObjectBrickPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickList\GetObjectBrickListPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickTree\GetObjectBrickTreePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ImportObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ImportObjectBrick\ImportObjectBrickPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\UpdateObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\UpdateObjectBrick\UpdateObjectBrickPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\StringIdBodyPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/class', name: 'opendxp_admin_dataobject_class_')]
#[IsGranted(CorePermission::Objectbricks->value)]
class ObjectBrickController extends AdminAbstractController
{
    #[Route('/objectbrick-get', name: 'objectbrickget', methods: ['GET'])]
    public function objectbrickGetAction(GetObjectBrickHandler $getObjectBrick, GetObjectBrickPayload $payload): JsonResponse
    {
        $result = $getObjectBrick($payload);
        $data = $result->data;
        $data['isWriteable'] = $result->isWriteable;

        return $this->adminJson($data);
    }

    #[Route('/objectbrick-update', name: 'objectbrickupdate', methods: ['PUT', 'POST'])]
    public function objectbrickUpdateAction(UpdateObjectBrickHandler $updateObjectBrick, UpdateObjectBrickPayload $payload): JsonResponse
    {
        $brickDef = $updateObjectBrick($payload);

        return $this->adminJson(ApiResponse::ok(['id' => $brickDef->getKey()]));
    }

    #[Route('/objectbrick-delete', name: 'objectbrickdelete', methods: ['DELETE'])]
    public function objectbrickDeleteAction(DeleteObjectBrickHandler $deleteObjectBrick, StringIdBodyPayload $payload): JsonResponse
    {
        $deleteObjectBrick($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/objectbrick-tree', name: 'objectbricktree', methods: ['GET', 'POST'])]
    public function objectbrickTreeAction(GetObjectBrickTreeHandler $getTree, GetObjectBrickTreePayload $payload): JsonResponse
    {
        $result = $getTree($payload);

        if ($payload->forObjectEditor) {
            return $this->adminJson(['objectbricks' => $result->definitions, 'layoutDefinitions' => $result->layoutDefinitions]);
        }

        return $this->adminJson($result->definitions);
    }

    #[Route('/objectbrick-list', name: 'objectbricklist', methods: ['GET'])]
    public function objectbrickListAction(GetObjectBrickListHandler $getList, GetObjectBrickListPayload $payload): JsonResponse
    {
        $result = $getList($payload);

        return $this->adminJson(['objectbricks' => $result->objectbricks]);
    }

    #[Route('/import-objectbrick', name: 'importobjectbrick', methods: ['POST'])]
    public function importObjectbrickAction(ImportObjectBrickHandler $importObjectBrick, ImportObjectBrickPayload $payload): JsonResponse
    {
        $importObjectBrick($payload);
        $response = $this->adminJson(ApiResponse::ok());
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/export-objectbrick', name: 'exportobjectbrick', methods: ['GET'])]
    public function exportObjectbrickAction(ExportObjectBrickHandler $exportObjectBrick, ExportObjectBrickPayload $payload): Response
    {
        $result = $exportObjectBrick($payload);

        $response = new Response($result->json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="objectbrick_' . $result->key . '_export.json"');

        return $response;
    }

    #[Route('/get-bricks-usages', name: 'getbrickusages', methods: ['GET'])]
    public function getBrickUsagesAction(GetBrickUsagesHandler $getBrickUsages, GetBrickUsagesPayload $payload): Response
    {
        return $this->adminJson($getBrickUsages($payload)->usages);
    }
}
