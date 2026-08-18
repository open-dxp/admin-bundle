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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\DoEmailLogExport;

use League\Flysystem\FilesystemException;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\Email\EmailLogListingFactory;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Logger;
use OpenDxp\Model\Tool\Email\Log;
use OpenDxp\Tool\Storage;
use RuntimeException;

final class DoEmailLogExportHandler
{
    private const string DELIMITER = ';';

    private const int PAGE_SIZE = 500;

    /**
     * The body columns are omitted: they are megabytes of markup
     */
    private const array COLUMNS = [
        'id',
        'documentId',
        'sentDate',
        'from',
        'replyTo',
        'to',
        'cc',
        'bcc',
        'subject',
        'params',
        'error',
    ];

    public function __construct(
        private readonly EmailLogListingFactory $listingFactory,
        private readonly GridExportService $gridExportService,
    ) {
    }

    public function __invoke(DoEmailLogExportPayload $payload): void
    {
        $temp = tmpfile();

        if ($temp === false) {
            throw new RuntimeException('Unable to open a temporary file for the CSV export');
        }

        try {
            fputcsv($temp, self::COLUMNS, self::DELIMITER, '"', '');

            $offset = 0;

            do {
                $list = $this->listingFactory->create($payload->documentId, $payload->filter, $payload->ids);
                $list->setLimit(self::PAGE_SIZE);
                $list->setOffset($offset);

                $entries = $list->getEmailLogs();

                foreach ($entries as $entry) {
                    fputcsv($temp, $this->toRow($entry), self::DELIMITER, '"', '');
                }

                $offset += self::PAGE_SIZE;

            } while (count($entries) === self::PAGE_SIZE);

            rewind($temp);

            Storage::get('temp')->writeStream(
                $this->gridExportService->getCsvFile($payload->fileHandle),
                $temp
            );
        } catch (FilesystemException $exception) {
            Logger::err($exception->getMessage());

            throw new AdminOperationFailedException(
                sprintf('export file could not be written: %s', $payload->fileHandle)
            );
        } finally {
            if (is_resource($temp)) {
                fclose($temp);
            }
        }
    }

    /**
     * @return array<int, string|int|null>
     */
    private function toRow(Log $entry): array
    {
        return [
            $entry->getId(),
            $entry->getDocumentId(),
            date('Y-m-d H:i:s', $entry->getSentDate()),
            $entry->getFrom(),
            $entry->getReplyTo(),
            $entry->getTo(),
            $entry->getCc(),
            $entry->getBcc(),
            $entry->getSubject(),
            json_encode($entry->getParams(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $entry->getError(),
        ];
    }
}
