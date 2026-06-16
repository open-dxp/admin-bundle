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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetBrickUsagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ImportObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\UpdateObjectBrickHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
    public function objectbrickGetAction(GetObjectBrickHandler $getObjectBrick, #[MapQueryParameter] string $id): JsonResponse
    {
        $result = $getObjectBrick($id);
        $data = $result->data;
        $data['isWriteable'] = $result->isWriteable;

        return $this->adminJson($data);
    }

    #[Route('/objectbrick-update', name: 'objectbrickupdate', methods: ['PUT', 'POST'])]
    public function objectbrickUpdateAction(UpdateObjectBrickHandler $updateObjectBrick, Request $request): JsonResponse
    {
        $brickDef = $updateObjectBrick(
            (string) $request->request->get('key'),
            (string) $request->request->get('title'),
            (string) $request->request->get('group'),
            $request->request->get('task') === 'add',
            $request->request->has('values') ? $this->decodeJson($request->request->get('values')) : null,
            $request->request->has('configuration') ? $this->decodeJson($request->request->get('configuration')) : null,
        );

        return $this->adminJson(ApiResponse::ok(['id' => $brickDef->getKey()]));
    }

    #[Route('/objectbrick-delete', name: 'objectbrickdelete', methods: ['DELETE'])]
    public function objectbrickDeleteAction(DeleteObjectBrickHandler $deleteObjectBrick, Request $request): JsonResponse
    {
        $deleteObjectBrick((string) $request->request->get('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/objectbrick-tree', name: 'objectbricktree', methods: ['GET', 'POST'])]
    public function objectbrickTreeAction(
        GetObjectBrickTreeHandler $getTree,
        #[MapQueryParameter] ?string $forObjectEditor = null,
        #[MapQueryParameter(name: 'object_id')] int $objectId = 0,
        #[MapQueryParameter(name: 'class_id')] ?string $classId = null,
        #[MapQueryParameter(name: 'field_name')] ?string $fieldName = null,
        #[MapQueryParameter] ?string $layoutId = null,
    ): JsonResponse {
        $result = $getTree(
            $forObjectEditor !== null,
            $objectId,
            $classId,
            $fieldName,
            $layoutId,
            );

        if ($forObjectEditor) {
            return $this->adminJson(['objectbricks' => $result->definitions, 'layoutDefinitions' => $result->layoutDefinitions]);
        }

        return $this->adminJson($result->definitions);
    }

    #[Route('/objectbrick-list', name: 'objectbricklist', methods: ['GET'])]
    public function objectbrickListAction(
        GetObjectBrickListHandler $getList,
        #[MapQueryParameter(name: 'class_id')] ?string $classId = null,
        #[MapQueryParameter(name: 'field_name')] ?string $fieldName = null,
        #[MapQueryParameter] ?string $layoutId = null,
        #[MapQueryParameter(name: 'object_id')] int $objectId = 0,
    ): JsonResponse {
        $result = $getList($classId, $fieldName, $layoutId, $objectId);

        return $this->adminJson(['objectbricks' => $result->objectbricks]);
    }

    #[Route('/import-objectbrick', name: 'importobjectbrick', methods: ['POST'])]
    public function importObjectbrickAction(ImportObjectBrickHandler $importObjectBrick, Request $request, #[MapQueryParameter] string $id): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('Filedata');
        $importObjectBrick($id, file_get_contents($file->getPathname()));
        $response = $this->adminJson(ApiResponse::ok());
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/export-objectbrick', name: 'exportobjectbrick', methods: ['GET'])]
    public function exportObjectbrickAction(ExportObjectBrickHandler $exportObjectBrick, #[MapQueryParameter] string $id): Response
    {
        $result = $exportObjectBrick($id);

        $response = new Response($result->json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="objectbrick_' . $result->key . '_export.json"');

        return $response;
    }

    #[Route('/get-bricks-usages', name: 'getbrickusages', methods: ['GET'])]
    public function getBrickUsagesAction(GetBrickUsagesHandler $getBrickUsages, #[MapQueryParameter] string $classId): Response
    {
        return $this->adminJson($getBrickUsages($classId)->usages);
    }
}
