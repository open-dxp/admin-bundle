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

use Exception;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToReadFile;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\File;
use OpenDxp\Tool\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

final class GridExportService
{
    public function __construct(private readonly GridHelperService $gridHelperService)
    {
    }

    public function getCsvFile(string $fileHandle): string
    {
        return $fileHandle . '.csv';
    }

    /**
     * @throws FilesystemException
     */
    public function downloadCsvFile(string $fileHandle): Response
    {
        $storage = Storage::get('temp');
        $csvFile = $this->getCsvFile(File::getValidFilename($fileHandle));

        try {
            $csvData = $storage->read($csvFile);
            $response = new Response($csvData);
            $response->headers->set('Content-Type', 'application/csv');
            $response->headers->set(
                'Content-Disposition',
                HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'export.csv')
            );
            $storage->delete($csvFile);

            return $response;
        } catch (FilesystemException | UnableToReadFile) {
            throw new RuntimeException('CSV file not found');
        }
    }

    /**
     * @throws FilesystemException
     */
    public function downloadXlsxFile(string $fileHandle): BinaryFileResponse
    {
        $storage = Storage::get('temp');
        $csvFile = $this->getCsvFile(File::getValidFilename($fileHandle));

        try {
            return $this->gridHelperService->createXlsxExportFile($storage, File::getValidFilename($fileHandle), $csvFile);
        } catch (Exception | FilesystemException | UnableToReadFile) {
            throw new RuntimeException('XLSX file not found');
        }
    }
}
