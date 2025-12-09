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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Document;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
use OpenDxp\Document\Editable\Block\BlockStateStack;
use OpenDxp\Document\Editable\EditmodeEditableDefinitionCollector;
use OpenDxp\Document\StaticPageGenerator;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use OpenDxp\Http\Request\Resolver\DocumentResolver;
use OpenDxp\Http\Request\Resolver\EditmodeResolver;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Messenger\GeneratePagePreviewMessage;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use OpenDxp\Model\Schedule\Task;
use OpenDxp\Templating\Renderer\EditableRenderer;
use OpenDxp\Tool\Frontend;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * @internal
 */
#[Route('/page', name: 'opendxp_admin_document_page_')]
class PageController extends DocumentControllerBase
{
    use RecursionBlockingEventDispatchHelperTrait;

    /**
     * @throws Exception
     */
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(Request $request, StaticPageGenerator $staticPageGenerator): JsonResponse
    {
        $page = Document\Page::getById((int)$request->get('id'));

        if (!$page) {
            throw $this->createNotFoundException('Page not found');
        }

        if (($lock = $this->checkForLock($page, $request->getSession()->getId())) instanceof JsonResponse) {
            return $lock;
        }

        $page = clone $page;
        $draftVersion = null;
        $page = $this->getLatestVersion($page, $draftVersion);

        $pageVersions = Element\Service::getSafeVersionInfo($page->getVersions());
        $page->setVersions(array_splice($pageVersions, -1, 1));
        $page->setParent(null);

        // unset useless data
        $page->setEditables(null);
        $page->setChildren(null);

        $data = $page->getObjectVars();
        $data['locked'] = $page->isLocked();

        $this->addTranslationsData($page, $data);
        $this->minimizeProperties($page, $data);
        $this->populateUsersNames($page, $data);

        if ($page->getContentMainDocument()) {
            $data['contentMainDocumentPath'] = $page->getContentMainDocument()->getRealFullPath();
        }

        if ($page->getStaticGeneratorEnabled()) {
            $data['staticLastGenerated'] = $staticPageGenerator->getLastModified($page);
        }

        $data['url'] = $page->getUrl();
        $data['scheduledTasks'] = array_map(
            static fn (Task $task) => $task->getObjectVars(),
            $page->getScheduledTasks()
        );

        return $this->preSendDataActions($data, $page, $draftVersion);
    }

    /**
     * @throws Exception
     */
    #[Route('/save', name: 'save', methods: ['PUT', 'POST'])]
    public function saveAction(Request $request, StaticPageGenerator $staticPageGenerator): JsonResponse
    {
        $oldPage = Document\Page::getById((int) $request->get('id'));
        if (!$oldPage) {
            throw $this->createNotFoundException('Page not found');
        }

        /** @var Document\Page|null $pageSession */
        $pageSession = $this->getFromSession($oldPage, $request->getSession());

        $page = $pageSession ?: $this->getLatestVersion($oldPage);

        if ($request->get('missingRequiredEditable') !== null) {
            $page->setMissingRequiredEditable($request->get('missingRequiredEditable') == 'true');
        }

        $settings = [];
        if ($request->get('settings')) {
            $settings = $this->decodeJson($request->get('settings'));
            if ($settings['published'] ?? false) {
                $page->setMissingRequiredEditable(null);
            }
        }

        [$task, $page, $version] = $this->saveDocument($page, $request);
        $arguments = [
            'oldPage' => $oldPage,
            'task' => $task,
        ];
        $documentEvent = new DocumentEvent($page, $arguments);
        $this->dispatchEvent($documentEvent, DocumentEvents::PAGE_POST_SAVE_ACTION);
        if ($task === self::TASK_PUBLISH || $task === self::TASK_UNPUBLISH) {
            $treeData = $this->getTreeNodeConfig($page);

            $data = [
                'versionDate' => $page->getModificationDate(),
                'versionCount' => $page->getVersionCount(),
            ];

            if ($staticGeneratorEnabled = $page->getStaticGeneratorEnabled()) {
                $data['staticGeneratorEnabled'] = $staticGeneratorEnabled;
                $data['staticLastGenerated'] = $staticPageGenerator->getLastModified($page);
            }

            return $this->adminJson([
                'success' => true,
                'treeData' => $treeData,
                'data' => $data,
            ]);
        }
        $this->saveToSession($page, $request->getSession());
        $draftData = [];
        if ($version) {
            $draftData = [
                'id' => $version->getId(),
                'modificationDate' => $version->getDate(),
                'isAutoSave' => $version->isAutoSave(),
            ];
        }
        $treeData = $this->getTreeNodeConfig($page);

        return $this->adminJson(['success' => true, 'treeData' => $treeData, 'draft' => $draftData]);
    }

    #[Route('/generate-previews', name: 'generatepreviews', methods: ['GET'])]
    public function generatePreviewsAction(Request $request, MessageBusInterface $messengerBusOpendxpCore): JsonResponse
    {
        $list = new Document\Listing();
        $list->setCondition('`type` = ?', ['page']);

        foreach ($list->loadIdList() as $docId) {
            $messengerBusOpendxpCore->dispatch(
                new GeneratePagePreviewMessage($docId, \OpenDxp\Tool::getHostUrl())
            );

            break;
        }

        return $this->adminJson(['success' => true]);
    }

    #[Route('/display-preview-image', name: 'display_preview_image', methods: ['GET'])]
    public function displayPreviewImageAction(Request $request): BinaryFileResponse
    {
        $document = Document\Page::getById((int) $request->get('id'));
        if ($document instanceof Document\Page) {
            return new BinaryFileResponse($document->getPreviewImageFilesystemPath(), 200, [
                'Content-Type' => 'image/jpg',
            ]);
        }

        throw $this->createNotFoundException('Page not found');
    }

    #[Route('/check-pretty-url', name: 'checkprettyurl', methods: ['POST'])]
    public function checkPrettyUrlAction(Request $request): JsonResponse
    {
        $docId = $request->request->getInt('id');
        $path = trim($request->request->get('path', ''));

        $success = true;

        if ($path === '') {
            return $this->adminJson([
                'success' => $success,
            ]);
        }

        $message = [];
        $path = rtrim($path, '/');

        // must start with /
        if ($path !== '' && !str_starts_with($path, '/')) {
            $success = false;
            $message[] = 'URL must start with /.';
        }

        if (strlen($path) < 2) {
            $success = false;
            $message[] = 'URL must be at least 2 characters long.';
        }

        if (!Element\Service::isValidPath($path, 'document')) {
            $success = false;
            $message[] = 'URL is invalid.';
        }

        $list = new Document\Listing();
        $list->setCondition('(CONCAT(`path`, `key`) = ? OR id IN (SELECT id from documents_page WHERE prettyUrl = ?))
            AND id != ?', [
            $path, $path, $docId,
        ]);

        if ($list->getTotalCount() > 0) {
            $checkDocument = Document::getById($docId);
            $checkSite     = Frontend::getSiteForDocument($checkDocument);
            $checkSiteId   = empty($checkSite) ? 0 : $checkSite->getId();

            foreach ($list as $document) {
                if (empty($document)) {
                    continue;
                }

                $site   = Frontend::getSiteForDocument($document);
                $siteId = empty($site) ? 0 : $site->getId();

                if ($siteId === $checkSiteId) {
                    $success   = false;
                    $message[] = 'URL path already exists.';

                    break;
                }
            }
        }

        return $this->adminJson([
            'success' => $success,
            'message' => implode('<br>', $message),
        ]);
    }

    #[Route('/clear-editable-data', name: 'cleareditabledata', methods: ['PUT'])]
    public function clearEditableDataAction(Request $request): JsonResponse
    {
        $docId = $request->request->getInt('id');
        $doc = Document\PageSnippet::getById($docId);

        if (!$doc) {
            throw $this->createNotFoundException('Document not found');
        }

        foreach ($doc->getEditables() as $editable) {
            // remove all but target group data
            // Hardcoded the TARGET_GROUP_EDITABLE_PREFIX prefix here as we shouldn't remove the bundle specific editables even if bundle is not enabled/installed
            if (!preg_match('/^' . preg_quote('persona_ -', '/') . '/', $editable->getName())) {
                $doc->removeEditable($editable->getName());
            }
        }

        $this->saveToSession($doc, $request->getSession(), true);

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/qr-code', name: 'qrcode', methods: ['GET'])]
    public function qrCodeAction(Request $request): BinaryFileResponse
    {
        $page = Document\Page::getById((int) $request->query->get('id'));

        if (!$page) {
            throw $this->createNotFoundException('Page not found');
        }

        $url = $page->getUrl();

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->size($request->query->get('download') ? 4000 : 500)
            ->build();

        $tmpFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/qr-code-' . uniqid() . '.png';
        $result->saveToFile($tmpFile);

        $response = new BinaryFileResponse($tmpFile);
        $response->headers->set('Content-Type', 'image/png');

        if ($request->query->get('download')) {
            $response->setContentDisposition('attachment', 'qrcode-preview.png');
        }

        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @throws NotFoundHttpException|Exception
     */
    #[Route('/areabrick-render-index-editmode', name: 'areabrick-render-index-editmode', methods: ['POST'])]
    public function areabrickRenderIndexEditmode(
        Request $request,
        BlockStateStack $blockStateStack,
        EditmodeEditableDefinitionCollector $definitionCollector,
        Environment $twig,
        EditableRenderer $editableRenderer,
        DocumentResolver $documentResolver,
        LocaleServiceInterface $localeService
    ): JsonResponse {
        $blockStateStackData = json_decode($request->get('blockStateStack'), true);
        $blockStateStack->loadArray($blockStateStackData);

        $document = Document\PageSnippet::getById((int) $request->get('documentId'));
        if (!$document) {
            throw $this->createNotFoundException();
        }

        $document = clone $document;
        $document->setEditables([]);
        $documentResolver->setDocument($request, $document);

        $twig->addGlobal('document', $document);
        $twig->addGlobal('editmode', true);

        // we can't use EditmodeResolver::setForceEditmode() here, because it would also render included documents in editmode
        // so we use the attribute as a workaround
        $request->attributes->set(EditmodeResolver::ATTRIBUTE_EDITMODE, true);

        // setting locale manually here before rendering, to make sure editables use the right locale from document
        $localeService->setLocale($document->getProperty('language'));

        $areaBlockConfig = json_decode($request->get('areablockConfig'), true);
        /** @var Document\Editable\Areablock $areablock */
        $areablock = $editableRenderer->getEditable($document, 'areablock', $request->get('realName'), $areaBlockConfig, true);
        $areablock->setRealName($request->get('realName'));
        $areablock->setEditmode(true);
        $areaBrickData = json_decode($request->get('areablockData'), true);
        $areablock->setDataFromEditmode($areaBrickData);
        $htmlCode = trim($areablock->renderIndex((int) $request->get('index'), true));

        return new JsonResponse([
            'editableDefinitions' => $definitionCollector->getDefinitions(),
            'htmlCode' => $htmlCode,
        ]);
    }

    protected function setValuesToDocument(Request $request, Document $document): void
    {
        $this->addSettingsToDocument($request, $document);
        $this->addDataToDocument($request, $document);
        $this->addPropertiesToDocument($request, $document);
        $this->applySchedulerDataToElement($request, $document, $this->getAdminUser());
    }
}
