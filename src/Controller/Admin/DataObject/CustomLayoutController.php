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

use OpenDxp\Bundle\AdminBundle\Attribute\AsHtmlContentTypeResponse;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\AddCustomLayout\AddCustomLayoutHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\AddCustomLayout\AddCustomLayoutPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\DeleteCustomLayout\DeleteCustomLayoutHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\ExportCustomLayout\ExportCustomLayoutHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\ExportCustomLayout\ExportCustomLayoutPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\GetAllLayouts\GetAllLayoutsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\GetCustomLayoutDefinitions\GetCustomLayoutDefinitionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\GetCustomLayoutDefinitions\GetCustomLayoutDefinitionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\GetCustomLayout\GetCustomLayoutHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\GetCustomLayout\GetCustomLayoutPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\ImportCustomLayout\ImportCustomLayoutHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\ImportCustomLayout\ImportCustomLayoutPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\SaveCustomLayout\SaveCustomLayoutHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\SaveCustomLayout\SaveCustomLayoutPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\SuggestCustomLayoutIdentifier\SuggestCustomLayoutIdentifierHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\SuggestCustomLayoutIdentifier\SuggestCustomLayoutIdentifierPayload;
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
#[IsGranted(CorePermission::Classes->value)]
class CustomLayoutController extends AdminAbstractController
{
    #[Route('/get-custom-layout', name: 'getcustomlayout', methods: ['GET'])]
    public function getCustomLayoutAction(GetCustomLayoutHandler $getCustomLayout, GetCustomLayoutPayload $payload): JsonResponse
    {
        $result = $getCustomLayout($payload);
        $data = $result->data;
        $data['isWriteable'] = $result->isWriteable;

        return $this->adminJson(ApiResponse::ok(['data' => $data]));
    }

    #[Route('/add-custom-layout', name: 'addcustomlayout', methods: ['POST'])]
    public function addCustomLayoutAction(AddCustomLayoutHandler $addCustomLayout, AddCustomLayoutPayload $payload): JsonResponse
    {
        $customLayout = $addCustomLayout($payload);

        $data = $customLayout->getObjectVars();
        $data['isWriteable'] = $customLayout->isWriteable();

        return $this->adminJson(ApiResponse::ok(['id' => $customLayout->getId(), 'name' => $customLayout->getName(), 'data' => $data]));
    }

    #[Route('/save-custom-layout', name: 'savecustomlayout', methods: ['PUT'])]
    public function saveCustomLayoutAction(SaveCustomLayoutHandler $saveCustomLayout, SaveCustomLayoutPayload $payload): JsonResponse
    {
        $customLayout = $saveCustomLayout($payload);

        return $this->adminJson(ApiResponse::ok(['id' => $customLayout->getId(), 'data' => $customLayout->getObjectVars()]));
    }

    #[Route('/delete-custom-layout', name: 'deletecustomlayout', methods: ['DELETE'])]
    public function deleteCustomLayoutAction(DeleteCustomLayoutHandler $deleteCustomLayout, StringIdBodyPayload $payload): JsonResponse
    {
        $deleteCustomLayout($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[AsHtmlContentTypeResponse]
    #[Route('/import-custom-layout-definition', name: 'importcustomlayoutdefinition', methods: ['POST', 'PUT'])]
    public function importCustomLayoutDefinitionAction(
        ImportCustomLayoutHandler $importCustomLayout,
        ImportCustomLayoutPayload $payload,
    ): Response {
        if ($payload->nameAlreadyInUse) {
            return $this->adminJson(ApiResponse::error(null, ['nameAlreadyInUse' => true]));
        }

        $importCustomLayout($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/export-custom-layout-definition', name: 'exportcustomlayoutdefinition', methods: ['GET'])]
    public function exportCustomLayoutDefinitionAction(ExportCustomLayoutHandler $exportCustomLayout, ExportCustomLayoutPayload $payload): Response
    {
        $result = $exportCustomLayout($payload);

        $response = new Response($result->json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename: "custom_definition_' . $result->name . '_export.json"');

        return $response;
    }

    #[Route('/get-custom-layout-definitions', name: 'getcustomlayoutdefinitions', methods: ['GET'])]
    public function getCustomLayoutDefinitionsAction(GetCustomLayoutDefinitionsHandler $getDefinitions, GetCustomLayoutDefinitionsPayload $payload): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok(['data' => $getDefinitions($payload)->definitions]));
    }

    #[Route('/get-all-layouts', name: 'getalllayouts', methods: ['GET'])]
    public function getAllLayoutsAction(GetAllLayoutsHandler $getAllLayouts): JsonResponse
    {
        return $this->adminJson(['data' => $getAllLayouts()->layouts]);
    }

    #[Route('/suggest-custom-layout-identifier', name: 'suggestcustomlayoutidentifier', methods: ['GET'])]
    public function suggestCustomLayoutIdentifierAction(SuggestCustomLayoutIdentifierHandler $suggestIdentifier, SuggestCustomLayoutIdentifierPayload $payload): Response
    {
        $result = $suggestIdentifier($payload);

        return $this->adminJson([
            'suggestedIdentifier' => $result->suggestedIdentifier,
            'existingIds' => $result->existingIds,
            'existingNames' => $result->existingNames,
        ]);
    }
}
