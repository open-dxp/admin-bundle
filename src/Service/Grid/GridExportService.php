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

namespace OpenDxp\Bundle\AdminBundle\Service\Grid;

use Generator;
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use LogicException;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportBatchStorage;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportFormat;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSettings;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceRegistry;
use OpenDxp\Bundle\AdminBundle\GridExport\SessionGridExportSourceInterface;
use OpenDxp\Bundle\AdminBundle\GridExport\Writer\CsvGridExportWriter;
use OpenDxp\Bundle\AdminBundle\GridExport\Writer\XlsxGridExportWriter;
use OpenDxp\Model\Tool\TmpStore;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Uid\Uuid;

final class GridExportService
{
    private const string STORE_PREFIX = 'grid_export_';

    private const string STORE_TAG = 'grid_export';

    private const int LIFETIME = 86400;

    public function __construct(
        private readonly GridExportSourceRegistry $sourceRegistry,
        private readonly GridExportBatchStorage $batchStorage,
        private readonly CsvGridExportWriter $csvWriter,
        private readonly XlsxGridExportWriter $xlsxWriter,
    ) {
    }

    public function createExport(string $source, GridExportQuery $query, GridExportSettings $settings): GridExport
    {
        $exportSource = $this->sourceRegistry->getSource($source);

        if ($exportSource instanceof SessionGridExportSourceInterface) {
            $query = new GridExportQuery(
                userId: $query->userId,
                language: $query->language,
                timezone: $query->timezone,
                parameters: $query->parameters,
                selectedIds: $query->selectedIds,
                sessionParameters: $exportSource->getSessionParameters($query),
            );
        }

        $export = new GridExport(
            id: Uuid::v4()->toRfc4122(),
            source: $source,
            query: $query,
            settings: $settings,
            columns: $exportSource->getColumns($query),
            total: $exportSource->countRows($query),
            batchSize: $this->sourceRegistry->getBatchSize($source),
        );

        TmpStore::set(self::STORE_PREFIX . $export->id, $export, self::STORE_TAG, self::LIFETIME);

        return $export;
    }

    public function findExport(string $id): ?GridExport
    {
        $export = TmpStore::get(self::STORE_PREFIX . $id)?->getData();

        return $export instanceof GridExport ? $export : null;
    }

    /**
     * Returns the values of each row in the order of the columns.
     *
     * @return Generator<int, list<mixed>>
     */
    public function getRows(GridExport $export, int $batch): Generator
    {
        if ($batch < 0 || $batch >= $export->getBatchCount()) {
            throw new InvalidArgumentException(sprintf('The export has no batch %d.', $batch));
        }

        $rows = $this->sourceRegistry
            ->getSource($export->source)
            ->getRows($export->query, $batch * $export->batchSize, $export->batchSize);

        foreach ($rows as $row) {
            yield $export->getValues($row);
        }
    }

    /**
     * @throws FilesystemException
     */
    public function writeBatch(GridExport $export, int $batch): void
    {
        $this->batchStorage->writeBatch($export, $batch, $this->getRows($export, $batch));
    }

    /**
     * @throws FilesystemException
     */
    public function isComplete(GridExport $export): bool
    {
        return $this->batchStorage->hasAllBatches($export);
    }

    /**
     * Writes the file of the export and removes the export. The response deletes the file once it is sent.
     *
     * @throws FilesystemException
     */
    public function createDownload(GridExport $export): BinaryFileResponse
    {
        $path = sprintf(
            '%s/grid-export-%s.%s',
            OPENDXP_SYSTEM_TEMP_DIRECTORY,
            $export->id,
            $export->settings->format->value,
        );

        if (!$this->isComplete($export)) {
            throw new LogicException(sprintf('The export "%s" misses batches.', $export->id));
        }

        $this->writeFile($export, $this->batchStorage->readRows($export), $path);
        $this->deleteExport($export->id);

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $export->settings->format->getContentType());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->getFilename($export));
        $response->deleteFileAfterSend();

        return $response;
    }

    /**
     * @param iterable<list<mixed>> $rows
     */
    public function writeFile(GridExport $export, iterable $rows, string $localPath): void
    {
        match ($export->settings->format) {
            GridExportFormat::CSV => $this->csvWriter->write($export, $rows, $localPath),
            GridExportFormat::XLSX => $this->xlsxWriter->write($export, $rows, $localPath),
        };
    }

    public function getFilename(GridExport $export): string
    {
        return sprintf('%s-%s.%s', $export->source, date('Y-m-d'), $export->settings->format->value);
    }

    /**
     * @throws FilesystemException
     */
    public function deleteExport(string $id): void
    {
        TmpStore::delete(self::STORE_PREFIX . $id);
        $this->batchStorage->deleteBatches($id);
    }
}
