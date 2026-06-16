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

use OpenDxp\Bundle\AdminBundle\Controller\Admin\ElementControllerBase;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Exception\ElementLockedException;
use OpenDxp\Controller\Traits\ElementEditLockHelperTrait;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Bundle\AdminBundle\Handler\Document\AddDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\ConvertDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\DeleteDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\TreeGetDocumentChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocumentDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocumentIdForPathHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocTypesByTypeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocTypesListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\ManageDocTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Site\GetSiteCustomSettingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Site\RemoveSiteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\AddDocumentTranslationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\CheckTranslationLanguageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\DetermineTranslationParentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\GetLanguageTreeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\GetLanguageTreeRootHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\RemoveDocumentTranslationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\UpdateDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\UpdateSiteHandler;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Tool;
use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/document')]
class DocumentController extends ElementControllerBase
{
    use ElementEditLockHelperTrait;

    public function __construct(
        ElementServiceInterface $elementService,
    ) {
        parent::__construct($elementService);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Override]
    #[Route('/tree-get-root', name: 'opendxp_admin_document_document_treegetroot', methods: ['GET'])]
    public function treeGetRootAction(
        #[MapQueryParameter] ?string $elementType = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $id = null,
    ): JsonResponse {
        return parent::treeGetRootAction($elementType, $id);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Override]
    #[Route('/delete-info', name: 'opendxp_admin_document_document_deleteinfo', methods: ['GET'])]
    public function deleteInfoAction(
        GetDeleteInfoHandler $handler,
        Request $request,
        #[MapQueryParameter] ?string $id = null,
        #[MapQueryParameter] ?string $type = null,
    ): JsonResponse {
        return parent::deleteInfoAction($handler, $request, $id, $type);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/get-data-by-id', name: 'opendxp_admin_document_document_getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetDocumentDataHandler $handler,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse
    {
        try {
            $result = $handler($id);
        } catch (ElementLockedException $e) {
            return $this->getEditLockResponse($e->getElementId(), $e->getElementType());
        }

        return $this->adminJson($result->data);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/tree-get-children-by-id', name: 'opendxp_admin_document_document_treegetchildrenbyid', methods: ['GET'])]
    public function treeGetChildrenByIdAction(
        TreeGetDocumentChildrenHandler $handler,
        Request $request,
        #[MapQueryParameter] int $inSearch = 0,
    ): JsonResponse
    {
        $result = $handler($request->query->all());

        if ($result->paginated) {
            return $this->adminJson([
                'offset' => $result->offset,
                'limit' => $result->limit,
                'total' => $result->total,
                'nodes' => $result->documents,
                'filter' => $result->filter ?: '',
                'inSearch' => $inSearch,
            ]);
        }

        return $this->adminJson($result->documents);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/add', name: 'opendxp_admin_document_document_add', methods: ['POST'])]
    public function addAction(Request $request, AddDocumentHandler $handler): JsonResponse
    {
        $result = $handler(
            parentId: $request->request->getInt('parentId'),
            type: $request->request->getString('type'),
            key: $request->request->getString('key'),
            docTypeId: $request->request->get('docTypeId'),
            translationsBaseDocumentId: $request->request->get('translationsBaseDocument'),
            language: $request->request->get('language'),
            inheritanceSource: $request->request->has('inheritanceSource') ? $request->request->get('inheritanceSource') : null,
            title: $request->request->get('title'),
            name: $request->request->get('name'),
        );

        return $this->adminJson(ApiResponse::ok([
            'id' => $result->document->getId(),
            'type' => $result->document->getType(),
        ]));
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/delete', name: 'opendxp_admin_document_document_delete', methods: ['DELETE'])]
    public function deleteAction(Request $request, DeleteDocumentHandler $handler): JsonResponse
    {
        $type = $request->request->getString('type');
        $id = $request->request->getInt('id');
        $amount = $request->request->getInt('amount');

        $result = $handler($type, $id, $amount);

        if ($type === 'children') {
            return $this->adminJson(ApiResponse::ok(['deleted' => $result->deleted]));
        }

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/update', name: 'opendxp_admin_document_document_update', methods: ['PUT'])]
    public function updateAction(Request $request, UpdateDocumentHandler $handler): JsonResponse
    {
        $updateData = [...$request->request->all(), ...$request->query->all()];

        $result = $handler((int) $request->request->get('id'), $updateData);

        return $this->adminJson(ApiResponse::ok(['treeData' => $result->treeData]));
    }

    #[Route('/doc-types', name: 'opendxp_admin_document_document_doctypesget', methods: ['GET'])]
    public function docTypesGetAction(GetDocTypesListHandler $getDocTypesList): JsonResponse
    {
        $result = $getDocTypesList();

        return $this->adminJson(ApiResponse::ok(['data' => $result->docTypes, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[IsGranted(CorePermission::DocumentTypes->value)]
    #[Route('/doc-types', name: 'opendxp_admin_document_document_doctypes', methods: ['PUT', 'POST', 'DELETE'])]
    public function docTypesAction(
        Request $request,
        ManageDocTypesHandler $handler,
        #[MapQueryParameter] ?string $xaction = null,
    ): JsonResponse
    {
        if ($request->request->get('data')) {
            $data = $this->decodeJson($request->request->get('data'));
            $result = $handler($xaction, $data);

            return $this->adminJson(ApiResponse::ok(['data' => $result->data]));
        }

        return $this->adminJson(false);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/get-doc-types', name: 'opendxp_admin_document_document_getdoctypes', methods: ['GET'])]
    public function getDocTypesAction(
        GetDocTypesByTypeHandler $getDocTypesByType,
        #[MapQueryParameter] ?string $type = null,
    ): JsonResponse
    {
        $result = $getDocTypesByType($type);

        return $this->adminJson(['docTypes' => $result->docTypes]);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/get-site-custom-settings', name: 'opendxp_admin_document_document_get_site_custom_settings', methods: ['POST'])]
    public function getSiteCustomSettingsAction(Request $request, GetSiteCustomSettingsHandler $getSiteCustomSettings): JsonResponse
    {
        $result = $getSiteCustomSettings($request->request->getInt('id'));

        return $this->adminJson(['data' => $result->nodes]);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/update-site', name: 'opendxp_admin_document_document_updatesite', methods: ['PUT'])]
    public function updateSiteAction(Request $request, UpdateSiteHandler $handler): JsonResponse
    {
        $domains = $request->request->getString('domains');
        $domains = str_replace(' ', '', $domains);
        $domains = $domains ? explode("\n", $domains) : [];

        $localizedErrorDocuments = [];
        foreach (Tool::getValidLanguages() as $language) {
            $requestValue = $request->request->get(sprintf('errorDocument_localized_%s', $language));
            if (isset($requestValue)) {
                $localizedErrorDocuments[$language] = $requestValue;
            }
        }

        $result = $handler(
            rootId: $request->request->getInt('id'),
            domains: $domains,
            mainDomain: $request->request->getString('mainDomain'),
            errorDocument: $request->request->getString('errorDocument'),
            localizedErrorDocuments: $localizedErrorDocuments,
            redirectToMainDomain: $request->request->getBoolean('redirectToMainDomain'),
            requestCustomSettings: $request->request->all(),
        );

        return $this->adminJson($result->siteVars);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/remove-site', name: 'opendxp_admin_document_document_removesite', methods: ['DELETE'])]
    public function removeSiteAction(Request $request, RemoveSiteHandler $removeSite): JsonResponse
    {
        $removeSite($request->request->getInt('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/get-id-for-path', name: 'opendxp_admin_document_document_getidforpath', methods: ['GET'])]
    public function getIdForPathAction(
        GetDocumentIdForPathHandler $getDocumentIdForPath,
        #[MapQueryParameter] ?string $path = null,
    ): JsonResponse
    {
        $result = $getDocumentIdForPath($path);
        if (!$result) {
            return $this->adminJson(false);
        }

        return $this->adminJson(['id' => $result->id, 'type' => $result->type]);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/language-tree', name: 'opendxp_admin_document_document_languagetree', methods: ['GET'])]
    public function languageTreeAction(
        GetLanguageTreeHandler $handler,
        #[MapQueryParameter] int $node = 0,
        #[MapQueryParameter] ?string $languages = null,
    ): JsonResponse
    {
        $result = $handler($node, explode(',', (string) $languages));

        return $this->adminJson($result->nodes);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/language-tree-root', name: 'opendxp_admin_document_document_languagetreeroot', methods: ['GET'])]
    public function languageTreeRootAction(
        GetLanguageTreeRootHandler $handler,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse
    {
        $result = $handler($id);

        return $this->adminJson([
            'root' => $result->root,
            'columns' => $result->columns,
            'languages' => $result->languages,
        ]);
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/convert', name: 'opendxp_admin_document_document_convert', methods: ['PUT'])]
    public function convertAction(Request $request, ConvertDocumentHandler $handler): JsonResponse
    {
        $handler((int) $request->request->get('id'), $request->request->get('type'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/translation-determine-parent', name: 'opendxp_admin_document_document_translationdetermineparent', methods: ['GET'])]
    public function translationDetermineParentAction(
        DetermineTranslationParentHandler $handler,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $language = null,
    ): JsonResponse
    {
        $result = $handler($id, $language);

        return $this->adminJson(ApiResponse::fromBool($result->found, [
            'targetPath' => $result->targetPath,
            'targetId' => $result->targetId,
        ]));
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/translation-add', name: 'opendxp_admin_document_document_translationadd', methods: ['POST'])]
    public function translationAddAction(Request $request, AddDocumentTranslationHandler $handler): JsonResponse
    {
        $handler($request->request->getInt('sourceId'), $request->request->getString('targetPath'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/translation-remove', name: 'opendxp_admin_document_document_translationremove', methods: ['DELETE'])]
    public function translationRemoveAction(Request $request, RemoveDocumentTranslationHandler $handler): JsonResponse
    {
        $handler($request->request->getInt('sourceId'), $request->request->getInt('targetId'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/translation-check-language', name: 'opendxp_admin_document_document_translationchecklanguage', methods: ['GET'])]
    public function translationCheckLanguageAction(
        CheckTranslationLanguageHandler $handler,
        #[MapQueryParameter] ?string $path = null,
    ): JsonResponse
    {
        $result = $handler($path);

        return $this->adminJson(ApiResponse::fromBool($result->found, [
            'language' => $result->language,
            'translationLinks' => $result->translationLinks,
        ]));
    }

    #[Override]
    public function getTreeNodeConfig(ElementInterface $element): array
    {
        return $this->elementService->getElementTreeNodeConfig($element);
    }
}
