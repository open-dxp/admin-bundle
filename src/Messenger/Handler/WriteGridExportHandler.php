<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Messenger\Handler;

use Generator;
use League\Flysystem\FilesystemException;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\Messenger\WriteGridExportMessage;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobProgress;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Handler\AbstractAutomationActionHandler;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunArtifact;
use OpenDxp\File;
use RuntimeException;

final class WriteGridExportHandler extends AbstractAutomationActionHandler
{
    public function __construct(
        private readonly GridExportService $gridExportService,
    ) {
        parent::__construct();
    }

    /**
     * @throws FilesystemException
     */
    public function __invoke(WriteGridExportMessage $message, JobProgress $progress): void
    {
        $exportId = $this->getCurrentJobStepConfig($message)['exportId'];
        $export = $this->gridExportService->findExport($exportId)
            ?? throw new RuntimeException(sprintf('No grid export exists as "%s".', $exportId));

        $progress->setTotal($export->total);

        $localPath = File::getLocalTempFilePath($export->settings->format->value);

        try {
            $this->gridExportService->writeFile($export, $this->getRows($progress, $export), $localPath);

            $stream = fopen($localPath, 'rb') ?: throw new RuntimeException(
                sprintf('The file "%s" of the grid export cannot be read.', $localPath),
            );

            try {
                $artifact = new JobRunArtifact($this->gridExportService->getFilename($export), $stream);
                $progress->setResult('grid_export_job_run_finished', artifacts: [$artifact]);
            } finally {
                fclose($stream);
            }
        } finally {
            $this->gridExportService->deleteExport($export->id);
            if (is_file($localPath)) {
                unlink($localPath);
            }
        }
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired('exportId');
        $this->stepConfiguration->setAllowedTypes('exportId', 'string');
    }

    /**
     * @return Generator<int, list<mixed>>
     */
    private function getRows(JobProgress $progress, GridExport $export): Generator
    {
        for ($batch = 0; $batch < $export->getBatchCount(); $batch++) {
            yield from $this->gridExportService->getRows($export, $batch);
            $progress->advance(min($export->batchSize, $export->total - $batch * $export->batchSize));

            // Each batch loads its elements into the runtime cache. Without this call the cache grows with every batch.
            OpenDxp::collectGarbage();
        }
    }
}
