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

use OpenDxp\Bundle\AdminBundle\Attribute\SessionIdentityAware;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\CheckPrettyUrl\CheckPrettyUrlHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\CheckPrettyUrl\CheckPrettyUrlPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GeneratePagePreviews\GeneratePagePreviewsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GenerateQrCode\GenerateQrCodeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GenerateQrCode\GenerateQrCodePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GetPageData\GetPageDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GetPageData\GetPageDataPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GetPagePreviewImagePath\GetPagePreviewImagePathHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GetPagePreviewImagePath\GetPagePreviewImagePathPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\RenderAreabrickIndexEditmode\RenderAreabrickIndexEditmodeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\ResetEditablesSession\ResetEditablesSessionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\ResetEditablesSession\ResetEditablesSessionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\SavePage\SavePageHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\SavePage\SavePagePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\RenderAreabrickIndexEditmode\RenderAreabrickIndexEditmodePayload;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/page', name: 'opendxp_admin_document_page_')]
class PageController extends DocumentControllerBase
{
    #[SessionIdentityAware]
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetPageDataHandler $handler,
        GetPageDataPayload $payload,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->preSendDataActions($result->data, $result->page);
    }

    #[SessionIdentityAware]
    #[Route('/save', name: 'save', methods: ['PUT', 'POST'])]
    public function saveAction(SavePagePayload $payload, SavePageHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }

    #[Route('/generate-previews', name: 'generatepreviews', methods: ['GET'])]
    public function generatePreviewsAction(GeneratePagePreviewsHandler $handler): JsonResponse
    {
        $handler();

        return $this->apiOk();
    }

    #[Route('/display-preview-image', name: 'display_preview_image', methods: ['GET'])]
    public function displayPreviewImageAction(
        GetPagePreviewImagePathHandler $handler,
        GetPagePreviewImagePathPayload $payload,
    ): BinaryFileResponse {
        $result = $handler($payload);

        return new BinaryFileResponse($result->filePath, 200, [
            'Content-Type' => 'image/jpg',
        ]);
    }

    #[Route('/check-pretty-url', name: 'checkprettyurl', methods: ['POST'])]
    public function checkPrettyUrlAction(CheckPrettyUrlPayload $payload, CheckPrettyUrlHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[SessionIdentityAware]
    #[Route('/clear-editable-data', name: 'cleareditabledata', methods: ['PUT'])]
    public function clearEditableDataAction(ResetEditablesSessionPayload $payload, ResetEditablesSessionHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/qr-code', name: 'qrcode', methods: ['GET'])]
    public function qrCodeAction(
        GenerateQrCodeHandler $handler,
        GenerateQrCodePayload $payload,
    ): BinaryFileResponse {
        $result = $handler($payload);

        $response = new BinaryFileResponse($result->filePath);
        $response->headers->set('Content-Type', 'image/png');

        if ($payload->download) {
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
        RenderAreabrickIndexEditmodePayload $payload,
        RenderAreabrickIndexEditmodeHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), envelope: false);
    }
}
