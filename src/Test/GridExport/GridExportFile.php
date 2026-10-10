<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Test\GridExport;

use InvalidArgumentException;
use OpenSpout\Reader\CSV\Options;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use ZipArchive;

final readonly class GridExportFile
{
    public function __construct(
        public string $filename,
        public string $content,
        public string $delimiter = ';',
    ) {
    }

    /**
     * Returns every row of the file, the header row included. The cells of an XLSX file keep their type.
     *
     * @return list<list<mixed>>
     */
    public function getRows(): array
    {
        $file = $this->createTemporaryFile();
        $reader = str_ends_with($this->filename, '.xlsx')
            ? new XlsxReader()
            : new CsvReader(new Options(FIELD_DELIMITER: $this->delimiter));
        $reader->open($file);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }
        $reader->close();
        unlink($file);

        return $rows;
    }

    /**
     * @return list<mixed>
     */
    public function getHeader(): array
    {
        return $this->getRows()[0];
    }

    /**
     * @return list<mixed>
     */
    public function getColumn(string $title): array
    {
        $rows = $this->getRows();
        $index = array_search($title, $rows[0], true);
        if ($index === false) {
            throw new InvalidArgumentException(sprintf('The file has no column "%s".', $title));
        }

        return array_column(array_slice($rows, 1), $index);
    }

    public function getWorksheet(): string
    {
        $file = $this->createTemporaryFile();
        $zip = new ZipArchive();
        $zip->open($file);
        $worksheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($file);

        return $worksheet;
    }

    public function createTemporaryFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'grid-export-');
        file_put_contents($file, $this->content);

        return $file;
    }
}
