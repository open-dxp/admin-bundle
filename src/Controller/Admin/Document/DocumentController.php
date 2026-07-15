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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Document;

use OpenDxp\Bundle\AdminBundle\Attribute\SessionIdentityAware;
use OpenDxp\Bundle\AdminBundle\Controller\Admin\ElementControllerBase;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Bundle\AdminBundle\Handler\Document\AddDocument\AddDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\AddDocument\AddDocumentPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\ConvertDocument\ConvertDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\ConvertDocument\ConvertDocumentPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\DeleteDocument\DeleteDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\DeleteDocument\DeleteDocumentPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\CreateDocType\CreateDocTypeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\DeleteDocType\DeleteDocTypeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\DocTypePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\UpdateDocType\UpdateDocTypeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocTypesByType\GetDocTypesByTypeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocTypesByType\GetDocTypesByTypePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\GetDocTypesList\GetDocTypesListHandler;
use OpenDxp\Bundle\AdminBundle\Payload\Common\EmptyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocumentData\GetDocumentDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocumentData\GetDocumentDataPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocumentIdForPath\GetDocumentIdForPathHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocumentIdForPath\GetDocumentIdForPathPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Site\GetSiteCustomSettings\GetSiteCustomSettingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Site\GetSiteCustomSettings\GetSiteCustomSettingsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Site\RemoveSite\RemoveSiteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Site\RemoveSite\RemoveSitePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Site\UpdateSite\UpdateSiteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Site\UpdateSite\UpdateSitePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\AddDocumentTranslation\AddDocumentTranslationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\AddDocumentTranslation\AddDocumentTranslationPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\CheckTranslationLanguage\CheckTranslationLanguageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\CheckTranslationLanguage\CheckTranslationLanguagePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\DetermineTranslationParent\DetermineTranslationParentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\DetermineTranslationParent\DetermineTranslationParentPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\GetLanguageTree\GetLanguageTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\GetLanguageTree\GetLanguageTreePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\GetLanguageTreeRoot\GetLanguageTreeRootHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\GetLanguageTreeRoot\GetLanguageTreeRootPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\RemoveDocumentTranslation\RemoveDocumentTranslationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\RemoveDocumentTranslation\RemoveDocumentTranslationPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\TreeGetDocumentChildren\TreeGetDocumentChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\TreeGetDocumentChildren\TreeGetDocumentChildrenPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\UpdateDocument\UpdateDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\UpdateDocument\UpdateDocumentPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfo\GetDeleteInfoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfo\GetDeleteInfoPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetTreeRoot\GetTreeRootHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetTreeRoot\GetTreeRootPayload;
use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/document')]
class DocumentController extends ElementControllerBase
{

    #[IsGranted(CorePermission::Documents->value)]
    #[Override]
    #[Route('/tree-get-root', name: 'opendxp_admin_document_document_treegetroot', methods: ['GET'])]
    public function treeGetRootAction(
        GetTreeRootPayload $payload,
        GetTreeRootHandler $handler,
    ): JsonResponse {
        return parent::treeGetRootAction($payload, $handler);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Override]
    #[Route('/delete-info', name: 'opendxp_admin_document_document_deleteinfo', methods: ['GET'])]
    public function deleteInfoAction(
        GetDeleteInfoHandler $handler,
        GetDeleteInfoPayload $payload,
    ): JsonResponse {
        return parent::deleteInfoAction($handler, $payload);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[SessionIdentityAware]
    #[Route('/get-data-by-id', name: 'opendxp_admin_document_document_getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetDocumentDataHandler $handler,
        GetDocumentDataPayload $payload,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/tree-get-children-by-id', name: 'opendxp_admin_document_document_treegetchildrenbyid', methods: ['GET'])]
    public function treeGetChildrenByIdAction(
        Request $request,
        TreeGetDocumentChildrenPayload $payload,
    ): Response
    {
        return match ($payload->hasPagination()) {
            true  => $this->forward(self::class . '::treeGetChildrenByIdPaginatedAction', [], $request->query->all()),
            false => $this->forward(self::class . '::treeGetChildrenByIdListAction', [], $request->query->all()),
        };
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/tree-get-children-by-id-paginated', name: 'opendxp_admin_document_document_treegetchildrenbyidpaginated', methods: ['GET'])]
    public function treeGetChildrenByIdPaginatedAction(
        TreeGetDocumentChildrenPayload $payload,
        TreeGetDocumentChildrenHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/tree-get-children-by-id-list', name: 'opendxp_admin_document_document_treegetchildrenbyidlist', methods: ['GET'])]
    public function treeGetChildrenByIdListAction(
        TreeGetDocumentChildrenPayload $payload,
        TreeGetDocumentChildrenHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload), rootProperty: 'nodes');
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/add', name: 'opendxp_admin_document_document_add', methods: ['POST'])]
    public function addAction(
        AddDocumentPayload $payload,
        AddDocumentHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/delete', name: 'opendxp_admin_document_document_delete', methods: ['DELETE'])]
    public function deleteAction(
        DeleteDocumentPayload $payload,
        DeleteDocumentHandler $handler,
    ): JsonResponse
    {
        $result = $handler($payload);

        if ($payload->type === 'children') {
            return $this->apiJson($result);
        }

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/update', name: 'opendxp_admin_document_document_update', methods: ['PUT'])]
    public function updateAction(
        UpdateDocumentPayload $payload,
        UpdateDocumentHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[Route('/doc-types', name: 'opendxp_admin_document_document_doctypesget', methods: ['GET'])]
    public function docTypesGetAction(
        EmptyPayload $payload,
        GetDocTypesListHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[IsGranted(CorePermission::DocumentTypes->value)]
    #[Route('/doc-types', name: 'opendxp_admin_document_document_doctypes', methods: ['PUT', 'POST', 'DELETE'])]
    public function docTypesAction(
        Request $request,
        #[MapQueryParameter] ?string $xaction = null,
    ): Response
    {
        return match ($xaction) {
            'destroy' => $this->forward(self::class . '::docTypesDestroyAction', [], $request->query->all()),
            'update'  => $this->forward(self::class . '::docTypesUpdateAction', [], $request->query->all()),
            'create'  => $this->forward(self::class . '::docTypesCreateAction', [], $request->query->all()),
            default   => throw new BadRequestHttpException(),
        };
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[IsGranted(CorePermission::DocumentTypes->value)]
    #[Route('/doc-types-destroy', name: 'opendxp_admin_document_document_doctypes_destroy', methods: ['PUT', 'POST', 'DELETE'])]
    public function docTypesDestroyAction(
        DocTypePayload $payload,
        DeleteDocTypeHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[IsGranted(CorePermission::DocumentTypes->value)]
    #[Route('/doc-types-update', name: 'opendxp_admin_document_document_doctypes_update', methods: ['PUT', 'POST', 'DELETE'])]
    public function docTypesUpdateAction(
        DocTypePayload $payload,
        UpdateDocTypeHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[IsGranted(CorePermission::DocumentTypes->value)]
    #[Route('/doc-types-create', name: 'opendxp_admin_document_document_doctypes_create', methods: ['PUT', 'POST', 'DELETE'])]
    public function docTypesCreateAction(
        DocTypePayload $payload,
        CreateDocTypeHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/get-doc-types', name: 'opendxp_admin_document_document_getdoctypes', methods: ['GET'])]
    public function getDocTypesAction(
        GetDocTypesByTypePayload $payload,
        GetDocTypesByTypeHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/get-site-custom-settings', name: 'opendxp_admin_document_document_get_site_custom_settings', methods: ['POST'])]
    public function getSiteCustomSettingsAction(
        GetSiteCustomSettingsPayload $payload,
        GetSiteCustomSettingsHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/update-site', name: 'opendxp_admin_document_document_updatesite', methods: ['PUT'])]
    public function updateSiteAction(
        UpdateSitePayload $payload,
        UpdateSiteHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload), rootProperty: 'siteVars');
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/remove-site', name: 'opendxp_admin_document_document_removesite', methods: ['DELETE'])]
    public function removeSiteAction(
        RemoveSitePayload $payload,
        RemoveSiteHandler $handler,
    ): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/get-id-for-path', name: 'opendxp_admin_document_document_getidforpath', methods: ['GET'])]
    public function getIdForPathAction(
        GetDocumentIdForPathPayload $payload,
        GetDocumentIdForPathHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/language-tree', name: 'opendxp_admin_document_document_languagetree', methods: ['GET'])]
    public function languageTreeAction(
        GetLanguageTreePayload $payload,
        GetLanguageTreeHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload), rootProperty: 'nodes');
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/language-tree-root', name: 'opendxp_admin_document_document_languagetreeroot', methods: ['GET'])]
    public function languageTreeRootAction(
        GetLanguageTreeRootPayload $payload,
        GetLanguageTreeRootHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/convert', name: 'opendxp_admin_document_document_convert', methods: ['PUT'])]
    public function convertAction(
        ConvertDocumentPayload $payload,
        ConvertDocumentHandler $handler,
    ): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/translation-determine-parent', name: 'opendxp_admin_document_document_translationdetermineparent', methods: ['GET'])]
    public function translationDetermineParentAction(
        DetermineTranslationParentPayload $payload,
        DetermineTranslationParentHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/translation-add', name: 'opendxp_admin_document_document_translationadd', methods: ['POST'])]
    public function translationAddAction(
        AddDocumentTranslationPayload $payload,
        AddDocumentTranslationHandler $handler,
    ): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/translation-remove', name: 'opendxp_admin_document_document_translationremove', methods: ['DELETE'])]
    public function translationRemoveAction(
        RemoveDocumentTranslationPayload $payload,
        RemoveDocumentTranslationHandler $handler,
    ): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/translation-check-language', name: 'opendxp_admin_document_document_translationchecklanguage', methods: ['GET'])]
    public function translationCheckLanguageAction(
        CheckTranslationLanguagePayload $payload,
        CheckTranslationLanguageHandler $handler,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

}
