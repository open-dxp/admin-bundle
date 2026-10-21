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

namespace OpenDxp\Bundle\AdminBundle\GridExport;

use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use OpenDxp\Tool\Storage;

/**
 * Keeps every batch in a file of its own, with one JSON line per row. The requests of one export can reach different
 * servers. A request never rewrites the rows of another request.
 */
final class GridExportBatchStorage
{
    private const string DIRECTORY = 'grid-export';

    /**
     * @param iterable<list<mixed>> $rows
     *
     * @throws FilesystemException
     */
    public function writeBatch(GridExport $export, int $batch, iterable $rows): void
    {
        $lines = '';
        foreach ($rows as $row) {
            $lines .= json_encode(array_map($this->encodeValue(...), $row), JSON_THROW_ON_ERROR) . "\n";
        }

        Storage::get('temp')->write($this->getBatchPath($export->id, $batch), $lines);
    }

    /**
     * @throws FilesystemException
     */
    public function hasAllBatches(GridExport $export): bool
    {
        $storage = Storage::get('temp');
        for ($batch = 0; $batch < $export->getBatchCount(); $batch++) {
            if (!$storage->fileExists($this->getBatchPath($export->id, $batch))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reads the rows of all batches in their order. Each call reads the batches again.
     *
     * @return Generator<int, list<mixed>>
     *
     * @throws FilesystemException
     */
    public function readRows(GridExport $export): Generator
    {
        $storage = Storage::get('temp');
        for ($batch = 0; $batch < $export->getBatchCount(); $batch++) {
            $stream = $storage->readStream($this->getBatchPath($export->id, $batch));

            try {
                while (($line = fgets($stream)) !== false) {
                    $values = json_decode($line, true, flags: JSON_THROW_ON_ERROR);

                    yield array_map(
                        $this->decodeValue(...),
                        array_column($export->columns, 'type'),
                        $values,
                    );
                }
            } finally {
                fclose($stream);
            }
        }
    }

    /**
     * @throws FilesystemException
     */
    public function deleteBatches(string $exportId): void
    {
        Storage::get('temp')->deleteDirectory(sprintf('%s/%s', self::DIRECTORY, $exportId));
    }

    /**
     * @return list<string>
     *
     * @throws FilesystemException
     */
    public function getExportIds(): array
    {
        return Storage::get('temp')
            ->listContents(self::DIRECTORY)
            ->filter(static fn (StorageAttributes $attributes): bool => $attributes->isDir())
            ->map(static fn (StorageAttributes $attributes): string => basename($attributes->path()))
            ->toArray();
    }

    private function getBatchPath(string $exportId, int $batch): string
    {
        return sprintf('%s/%s/%d.jsonl', self::DIRECTORY, $exportId, $batch);
    }

    private function encodeValue(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        return $value;
    }

    private function decodeValue(GridExportColumnType $type, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            GridExportColumnType::DATE, GridExportColumnType::DATETIME => new DateTimeImmutable($value),
            default => $value,
        };
    }
}
