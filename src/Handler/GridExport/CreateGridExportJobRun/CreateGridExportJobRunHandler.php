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

namespace OpenDxp\Bundle\AdminBundle\Handler\GridExport\CreateGridExportJobRun;

use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportJobRunService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CreateGridExportJobRunHandler
{
    public function __construct(
        // The service exists only when grid exports run in the Generic Execution Engine.
        private readonly ?GridExportJobRunService $gridExportJobRunService,
    ) {
    }

    public function __invoke(GridExport $export): CreateGridExportJobRunResult
    {
        $jobRunService = $this->gridExportJobRunService;
        if ($jobRunService === null || !$jobRunService->canCreateJobRun()) {
            throw new ConflictHttpException(sprintf('The grid export "%s" runs in the browser.', $export->id));
        }

        $jobRun = $jobRunService->createJobRun($export);

        return new CreateGridExportJobRunResult(jobRunId: $jobRun->getId());
    }
}
