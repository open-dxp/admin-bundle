<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport\Writer;

use League\Flysystem\FilesystemException;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportBatchStorage;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumnType;
use OpenDxp\Model\Element\Service;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CsvGridExportWriter
{
    // Excel reads a CSV file as UTF-8 only when it starts with the byte order mark.
    private const string BYTE_ORDER_MARK = "\xEF\xBB\xBF";

    public function __construct(
        private readonly GridExportBatchStorage $batchStorage,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws FilesystemException
     */
    public function write(GridExport $export, string $path): void
    {
        $file = fopen($path, 'wb');
        if ($file === false) {
            throw new RuntimeException(sprintf('Unable to open "%s" for the CSV export.', $path));
        }

        try {
            fwrite($file, self::BYTE_ORDER_MARK);

            $titles = $export->settings->header->getTitles($export->columns);
            if ($titles !== null) {
                $this->writeRecord($file, $export, Service::escapeCsvRecord($titles));
            }

            $types = array_column($export->columns, 'type');
            foreach ($this->batchStorage->readRows($export) as $row) {
                $this->writeRecord($file, $export, array_map(
                    fn (GridExportColumnType $type, mixed $value): string => $this->formatValue($export, $type, $value),
                    $types,
                    $row,
                ));
            }
        } finally {
            fclose($file);
        }
    }

    /**
     * @param resource $file
     * @param list<string> $record
     */
    private function writeRecord($file, GridExport $export, array $record): void
    {
        fputcsv($file, $record, $export->settings->delimiter, '"', '');
    }

    private function formatValue(GridExport $export, GridExportColumnType $type, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return match ($type) {
            GridExportColumnType::STRING => Service::escapeCsvRecord([$value])[0],
            GridExportColumnType::INTEGER, GridExportColumnType::FLOAT => (string) $value,
            GridExportColumnType::BOOLEAN => $this->translator->trans(
                $value ? 'yes' : 'no',
                domain: 'admin_ext',
                locale: $export->query->language,
            ),
            GridExportColumnType::DATE => $value->format('Y-m-d'),
            GridExportColumnType::DATETIME => $value->format('Y-m-d H:i:s'),
        };
    }
}
