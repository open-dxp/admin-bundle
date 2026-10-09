<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport\Writer;

use League\Flysystem\FilesystemException;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportBatchStorage;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumnType;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

final class XlsxGridExportWriter
{
    private const int MINIMUM_COLUMN_WIDTH = 8;

    private const int MAXIMUM_COLUMN_WIDTH = 60;

    private readonly Style $dateStyle;

    private readonly Style $dateTimeStyle;

    public function __construct(private readonly GridExportBatchStorage $batchStorage)
    {
        $this->dateStyle = (new Style())->withFormat('yyyy-mm-dd');
        $this->dateTimeStyle = (new Style())->withFormat('yyyy-mm-dd hh:mm:ss');
    }

    /**
     * Writes the rows with a type per cell. A header row stays visible while scrolling and filters its column.
     *
     * @throws FilesystemException
     */
    public function write(GridExport $export, string $path): void
    {
        $types = array_column($export->columns, 'type');
        $titles = $export->settings->header->getTitles($export->columns);
        $widths = array_map(
            static fn (?string $title): int => mb_strlen($title ?? ''),
            $titles ?? array_fill(0, count($types), null),
        );

        $writer = new Writer();
        $writer->openToFile($path);

        if ($titles !== null) {
            $bold = (new Style())->withFontBold(true);
            $writer->addRow(new Row(array_map(
                static fn (string $title): StringCell => new StringCell($title, $bold),
                $titles,
            )));
        }

        $rowCount = 0;
        foreach ($this->batchStorage->readRows($export) as $row) {
            $cells = [];
            foreach ($row as $index => $value) {
                $cells[] = $this->createCell($types[$index], $value);
                $widths[$index] = max($widths[$index], $this->measureValue($types[$index], $value));
            }

            $writer->addRow(new Row($cells));
            $rowCount++;
        }

        // OpenSpout writes the widths, the filter and the view of a sheet when it closes the file.
        $sheet = $writer->getCurrentSheet();
        foreach ($widths as $index => $width) {
            $sheet->setColumnWidth(
                min(max($width + 2, self::MINIMUM_COLUMN_WIDTH), self::MAXIMUM_COLUMN_WIDTH),
                $index + 1,
            );
        }

        if ($titles !== null) {
            $sheet->setSheetView(new SheetView(freezeRow: 2));
            $sheet->setAutoFilter(new AutoFilter(0, 1, count($titles) - 1, $rowCount + 1));
        }

        $writer->close();
    }

    private function createCell(GridExportColumnType $type, mixed $value): Cell
    {
        if ($value === null) {
            return Cell::fromValue(null);
        }

        // Cell::fromValue() turns a string that starts with "=" into a formula.
        return match ($type) {
            GridExportColumnType::STRING => new StringCell($value),
            GridExportColumnType::DATE => Cell::fromValue($value, $this->dateStyle),
            GridExportColumnType::DATETIME => Cell::fromValue($value, $this->dateTimeStyle),
            default => Cell::fromValue($value),
        };
    }

    private function measureValue(GridExportColumnType $type, mixed $value): int
    {
        return match ($type) {
            GridExportColumnType::BOOLEAN => 5,
            GridExportColumnType::DATE => 10,
            GridExportColumnType::DATETIME => 19,
            default => mb_strlen((string) $value),
        };
    }
}
