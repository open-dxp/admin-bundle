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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Document;

use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Exception\ElementLockedException;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\CheckPrettyUrlHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GeneratePagePreviewsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GenerateQrCodeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GetPageDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GetPagePreviewImagePathHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\RenderAreabrickIndexEditmodeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\ResetEditablesSessionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\SavePageHandler;
use OpenDxp\Bundle\AdminBundle\Payload\Document\PagePayload;
use OpenDxp\Bundle\AdminBundle\Payload\Document\RenderAreabrickIndexEditmodePayload;
use OpenDxp\Document\StaticPageGenerator;
use OpenDxp\Http\Request\Resolver\DocumentResolver;
use OpenDxp\Http\Request\Resolver\EditmodeResolver;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * @internal
 */
#[Route('/page', name: 'opendxp_admin_document_page_')]
class PageController extends DocumentControllerBase
{
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetPageDataHandler $handler,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse
    {
        try {
            $result = $handler($id);
        } catch (ElementLockedException $e) {
            return $this->getEditLockResponse($e->getElementId(), $e->getElementType());
        }

        return $this->preSendDataActions($result->data, $result->page);
    }

    #[Route('/save', name: 'save', methods: ['PUT', 'POST'])]
    public function saveAction(Request $request, StaticPageGenerator $staticPageGenerator, SavePageHandler $handler): JsonResponse
    {
        try {
            $result = $handler((int) $request->request->get('id'), PagePayload::fromRequest($request));
        } catch (ElementLockedException $e) {
            return $this->getEditLockResponse($e->getElementId(), $e->getElementType());
        }

        if ($result->task === self::TASK_PUBLISH || $result->task === self::TASK_UNPUBLISH) {
            $data = [
                'versionDate' => $result->page->getModificationDate(),
                'versionCount' => $result->page->getVersionCount(),
            ];
            if ($staticGeneratorEnabled = $result->page->getStaticGeneratorEnabled()) {
                $data['staticGeneratorEnabled'] = $staticGeneratorEnabled;
                $data['staticLastGenerated'] = $staticPageGenerator->getLastModified($result->page);
            }

            return $this->adminJson(ApiResponse::ok(['treeData' => $result->treeData, 'data' => $data]));
        }

        $draftData = [];
        if ($result->version) {
            $draftData = [
                'id' => $result->version->getId(),
                'modificationDate' => $result->version->getDate(),
                'isAutoSave' => $result->version->isAutoSave(),
            ];
        }

        return $this->adminJson(ApiResponse::ok(['treeData' => $result->treeData, 'draft' => $draftData]));
    }

    #[Route('/generate-previews', name: 'generatepreviews', methods: ['GET'])]
    public function generatePreviewsAction(GeneratePagePreviewsHandler $handler): JsonResponse
    {
        $handler();

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/display-preview-image', name: 'display_preview_image', methods: ['GET'])]
    public function displayPreviewImageAction(
        GetPagePreviewImagePathHandler $handler,
        #[MapQueryParameter] int $id = 0,
    ): BinaryFileResponse
    {
        $filePath = $handler($id);

        return new BinaryFileResponse($filePath, 200, [
            'Content-Type' => 'image/jpg',
        ]);
    }

    #[Route('/check-pretty-url', name: 'checkprettyurl', methods: ['POST'])]
    public function checkPrettyUrlAction(Request $request, CheckPrettyUrlHandler $handler): JsonResponse
    {
        $docId = $request->request->getInt('id');
        $path = trim($request->request->get('path', ''));

        $result = $handler($docId, $path);

        return $this->adminJson(ApiResponse::fromBool($result->success, ['message' => implode('<br>', $result->messages)]));
    }

    #[Route('/clear-editable-data', name: 'cleareditabledata', methods: ['PUT'])]
    public function clearEditableDataAction(Request $request, ResetEditablesSessionHandler $handler): JsonResponse
    {
        $handler($request->request->getInt('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/qr-code', name: 'qrcode', methods: ['GET'])]
    public function qrCodeAction(
        GenerateQrCodeHandler $handler,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $download = null,
    ): BinaryFileResponse
    {
        $tmpFile = $handler($id, (bool) $download);

        $response = new BinaryFileResponse($tmpFile);
        $response->headers->set('Content-Type', 'image/png');

        if ($download) {
            $response->setContentDisposition('attachment', 'qrcode-preview.png');
        }

        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @throws NotFoundHttpException
     */
    #[Route('/areabrick-render-index-editmode', name: 'areabrick-render-index-editmode', methods: ['POST'])]
    public function areabrickRenderIndexEditmode(
        Request $request,
        RenderAreabrickIndexEditmodeHandler $renderAreabrickIndexEditmode,
        DocumentResolver $documentResolver,
        Environment $twig,
    ): JsonResponse {
        $request->attributes->set(EditmodeResolver::ATTRIBUTE_EDITMODE, true);

        $result = $renderAreabrickIndexEditmode(RenderAreabrickIndexEditmodePayload::fromRequest($request));

        $documentResolver->setDocument($request, $result->document);
        $twig->addGlobal('document', $result->document);
        $twig->addGlobal('editmode', true);

        return new JsonResponse([
            'editableDefinitions' => $result->editableDefinitions,
            'htmlCode' => $result->htmlCode,
        ]);
    }
}
