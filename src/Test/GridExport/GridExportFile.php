<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Test\GridExport;

use InvalidArgumentException;
use OpenSpout\Reader\XLSX\Reader;
use ZipArchive;

/**
 * Holds the file of a grid export and reads its rows.
 */
final readonly class GridExportFile
{
    public function __construct(
        public string $filename,
        public string $content,
        public string $delimiter = ';',
    ) {
    }

    /**
     * Returns every row of the file, the header row included. An XLSX file hands its cells over with their type.
     *
     * @return list<list<mixed>>
     */
    public function rows(): array
    {
        return str_ends_with($this->filename, '.xlsx') ? $this->readSpreadsheet() : $this->readCsv();
    }

    /**
     * @return list<mixed>
     */
    public function header(): array
    {
        return $this->rows()[0];
    }

    /**
     * Returns the values below the title of a column.
     *
     * @return list<mixed>
     */
    public function column(string $title): array
    {
        $rows = $this->rows();
        $index = array_search($title, $rows[0], true);
        if ($index === false) {
            throw new InvalidArgumentException(sprintf('The file has no column "%s".', $title));
        }

        return array_column(array_slice($rows, 1), $index);
    }

    public function worksheet(): string
    {
        $file = $this->writeTemporaryFile();
        $zip = new ZipArchive();
        $zip->open($file);
        $worksheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($file);

        return $worksheet;
    }

    /**
     * @return list<list<mixed>>
     */
    private function readCsv(): array
    {
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $this->content));
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, separator: $this->delimiter, escape: '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function readSpreadsheet(): array
    {
        $file = $this->writeTemporaryFile();
        $reader = new Reader();
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

    public function writeTemporaryFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'grid-export-');
        file_put_contents($file, $this->content);

        return $file;
    }
}
