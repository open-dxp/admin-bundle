<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\GridExport\StartGridExport;

use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;

final class StartGridExportHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GridExportService $gridExportService,
    ) {
    }

    public function __invoke(StartGridExportPayload $payload): StartGridExportResult
    {
        $query = new GridExportQuery(
            userId: $this->userContext->getAdminUser()->getId(),
            language: $payload->language,
            timezone: $payload->timezone,
            parameters: $payload->parameters,
            selectedIds: $payload->selectedIds,
        );

        $export = $this->gridExportService->createExport($payload->source, $query, $payload->settings);

        return new StartGridExportResult(
            id: $export->id,
            total: $export->total,
            batchSize: $export->batchSize,
            batchCount: $export->getBatchCount(),
        );
    }
}
