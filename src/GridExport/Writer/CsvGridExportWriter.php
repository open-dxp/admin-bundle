<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport\Writer;

use League\Flysystem\FilesystemException;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportBatchStorage;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumnType;
use OpenDxp\Model\Element\Service;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Writer\CSV\Writer;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CsvGridExportWriter
{
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
        $writer = new Writer(new Options(FIELD_DELIMITER: $export->settings->delimiter));
        $writer->openToFile($path);

        $titles = $export->settings->header->getTitles($export->columns);
        if ($titles !== null) {
            $writer->addRow($this->createRow(Service::escapeCsvRecord($titles)));
        }

        $types = array_column($export->columns, 'type');
        foreach ($this->batchStorage->readRows($export) as $row) {
            $writer->addRow($this->createRow(array_map(
                fn (GridExportColumnType $type, mixed $value): string => $this->formatValue($export, $type, $value),
                $types,
                $row,
            )));
        }

        $writer->close();
    }

    /**
     * @param list<string> $values
     */
    private function createRow(array $values): Row
    {
        return new Row(array_map(static fn (string $value): StringCell => new StringCell($value, null), $values));
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
