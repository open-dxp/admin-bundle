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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\AddCustomLayoutHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\DeleteCustomLayoutHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\ExportCustomLayoutHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\GetAllLayoutsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\GetCustomLayoutDefinitionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\GetCustomLayoutHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\ImportCustomLayoutHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\SaveCustomLayoutHandler;
use OpenDxp\Model\DataObject;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\SuggestCustomLayoutIdentifierHandler;
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
#[IsGranted(CorePermission::Classes->value)]
class CustomLayoutController extends AdminAbstractController
{
    #[Route('/get-custom-layout', name: 'getcustomlayout', methods: ['GET'])]
    public function getCustomLayoutAction(GetCustomLayoutHandler $getCustomLayout, #[MapQueryParameter] string $id): JsonResponse
    {
        $result = $getCustomLayout($id);
        $data = $result->data;
        $data['isWriteable'] = $result->isWriteable;

        return $this->adminJson(ApiResponse::ok(['data' => $data]));
    }

    #[Route('/add-custom-layout', name: 'addcustomlayout', methods: ['POST'])]
    public function addCustomLayoutAction(AddCustomLayoutHandler $addCustomLayout, Request $request): JsonResponse
    {
        $customLayout = $addCustomLayout(
            (string) $request->request->get('layoutIdentifier'),
            (string) $request->request->get('layoutName'),
            (string) $request->request->get('classId'),
            );

        $data = $customLayout->getObjectVars();
        $data['isWriteable'] = $customLayout->isWriteable();

        return $this->adminJson(ApiResponse::ok(['id' => $customLayout->getId(), 'name' => $customLayout->getName(), 'data' => $data]));
    }

    #[Route('/save-custom-layout', name: 'savecustomlayout', methods: ['PUT'])]
    public function saveCustomLayoutAction(SaveCustomLayoutHandler $saveCustomLayout, Request $request): JsonResponse
    {
        $customLayout = $saveCustomLayout(
            (string) $request->request->get('id'),
            $this->decodeJson($request->request->get('configuration')),
            $this->decodeJson($request->request->get('values')),
        );

        return $this->adminJson(ApiResponse::ok(['id' => $customLayout->getId(), 'data' => $customLayout->getObjectVars()]));
    }

    #[Route('/delete-custom-layout', name: 'deletecustomlayout', methods: ['DELETE'])]
    public function deleteCustomLayoutAction(DeleteCustomLayoutHandler $deleteCustomLayout, Request $request): JsonResponse
    {
        $deleteCustomLayout((string) $request->request->get('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/import-custom-layout-definition', name: 'importcustomlayoutdefinition', methods: ['POST', 'PUT'])]
    public function importCustomLayoutDefinitionAction(
        ImportCustomLayoutHandler $importCustomLayout,
        Request $request,
        #[MapQueryParameter] ?string $id = null,
    ): Response {
        /** @var UploadedFile $file */
        $file = $request->files->get('Filedata');
        $importData = $this->decodeJson(file_get_contents($file->getPathname()));

        if (isset($importData['name']) && DataObject\ClassDefinition\CustomLayout::getByName($importData['name']) instanceof DataObject\ClassDefinition\CustomLayout) {
            $response = $this->adminJson(ApiResponse::error(null, ['nameAlreadyInUse' => true]));
            $response->headers->set('Content-Type', 'text/html');

            return $response;
        }

        $importCustomLayout($id, $importData);

        $response = $this->adminJson(ApiResponse::ok());
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/export-custom-layout-definition', name: 'exportcustomlayoutdefinition', methods: ['GET'])]
    public function exportCustomLayoutDefinitionAction(ExportCustomLayoutHandler $exportCustomLayout, #[MapQueryParameter] ?string $id = null): Response
    {
        $result = $exportCustomLayout($id);

        $response = new Response($result->json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename: "custom_definition_' . $result->name . '_export.json"');

        return $response;
    }

    #[Route('/get-custom-layout-definitions', name: 'getcustomlayoutdefinitions', methods: ['GET'])]
    public function getCustomLayoutDefinitionsAction(GetCustomLayoutDefinitionsHandler $getDefinitions, #[MapQueryParameter] string $classId): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok(['data' => $getDefinitions($classId)->definitions]));
    }

    #[Route('/get-all-layouts', name: 'getalllayouts', methods: ['GET'])]
    public function getAllLayoutsAction(GetAllLayoutsHandler $getAllLayouts): JsonResponse
    {
        return $this->adminJson(['data' => $getAllLayouts()->layouts]);
    }

    #[Route('/suggest-custom-layout-identifier', name: 'suggestcustomlayoutidentifier', methods: ['GET'])]
    public function suggestCustomLayoutIdentifierAction(SuggestCustomLayoutIdentifierHandler $suggestIdentifier, #[MapQueryParameter] string $classId): Response
    {
        $result = $suggestIdentifier($classId);

        return $this->adminJson([
            'suggestedIdentifier' => $result->suggestedIdentifier,
            'existingIds' => $result->existingIds,
            'existingNames' => $result->existingNames,
        ]);
    }
}
