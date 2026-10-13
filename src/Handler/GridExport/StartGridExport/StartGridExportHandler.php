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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\GridExport\StartGridExport;

use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportJobRunService;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;

final class StartGridExportHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GridExportService $gridExportService,
        // The service exists only when grid exports run in the Generic Execution Engine.
        private readonly ?GridExportJobRunService $gridExportJobRunService,
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
            canCreateJobRun: $this->gridExportJobRunService?->canCreateJobRun() ?? false,
        );
    }
}
