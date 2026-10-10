<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceRegistry;
use OpenDxp\Bundle\AdminBundle\Handler\GridExport\StartGridExport\StartGridExportHandler;
use OpenDxp\Bundle\AdminBundle\Handler\GridExport\StartGridExport\StartGridExportPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\StringIdBodyPayload;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/grid-export', name: 'opendxp_admin_gridexport_')]
final class GridExportController extends AdminAbstractController
{
    #[Route('/start', name: 'start', methods: ['POST'])]
    public function startAction(
        StartGridExportPayload $payload,
        StartGridExportHandler $handler,
        GridExportSourceRegistry $sourceRegistry,
    ): JsonResponse {
        if (!$sourceRegistry->hasSource($payload->source)) {
            throw $this->createNotFoundException(
                sprintf('No grid export source is registered as "%s".', $payload->source),
            );
        }

        $this->denyAccessUnlessGranted($sourceRegistry->getPermission($payload->source));

        return $this->apiJson($handler($payload));
    }

    /**
     * @throws FilesystemException
     */
    #[Route('/batch', name: 'batch', methods: ['POST'])]
    public function writeBatchAction(Request $request, GridExportService $gridExportService): JsonResponse
    {
        $export = $this->getOwnExport($gridExportService, $request->request->getString('id'));

        try {
            $gridExportService->writeBatch($export, $request->request->getInt('batch'));
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        return $this->apiOk();
    }

    /**
     * @throws FilesystemException
     */
    #[Route('/download', name: 'download', methods: ['GET'])]
    public function downloadAction(
        GridExportService $gridExportService,
        #[MapQueryParameter] string $id,
    ): BinaryFileResponse {
        $export = $this->getOwnExport($gridExportService, $id);
        if (!$gridExportService->isComplete($export)) {
            throw new ConflictHttpException(sprintf('The grid export "%s" misses batches.', $id));
        }

        return $gridExportService->createDownload($export);
    }

    /**
     * @throws FilesystemException
     */
    #[Route('/delete', name: 'delete', methods: ['POST'])]
    public function deleteAction(StringIdBodyPayload $payload, GridExportService $gridExportService): JsonResponse
    {
        $gridExportService->deleteExport($this->getOwnExport($gridExportService, $payload->id)->id);

        return $this->apiOk();
    }

    private function getOwnExport(GridExportService $gridExportService, string $id): GridExport
    {
        $export = $gridExportService->findExport($id);
        if ($export === null || $export->query->userId !== $this->getAdminUser()?->getId()) {
            throw $this->createNotFoundException(sprintf('No grid export exists as "%s".', $id));
        }

        return $export;
    }
}
