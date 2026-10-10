<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport\Source;

use DateTimeImmutable;
use DateTimeZone;
use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumn;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumnType;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Bundle\AdminBundle\Service\Email\EmailLogListingFactory;
use OpenDxp\Model\Tool\Email\Log;
use OpenDxp\Model\Tool\Email\Log\Listing;
use OpenDxp\Security\CorePermission;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsGridExportSource(name: 'email-logs', permission: CorePermission::Emails->value)]
final class EmailLogGridExportSource implements GridExportSourceInterface
{
    public function __construct(
        private readonly EmailLogListingFactory $listingFactory,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getColumns(GridExportQuery $query): array
    {
        $label = fn (string $key): string => $this->translator->trans($key, domain: 'admin', locale: $query->language);

        return [
            new GridExportColumn('id', $label('id'), GridExportColumnType::INTEGER),
            new GridExportColumn('documentId', $label('document_id'), GridExportColumnType::INTEGER),
            new GridExportColumn('sentDate', $label('email_log_sent_Date'), GridExportColumnType::DATETIME),
            new GridExportColumn('from', $label('email_log_from')),
            new GridExportColumn('replyTo', $label('email_reply_to')),
            new GridExportColumn('to', $label('email_log_to')),
            new GridExportColumn('cc', $label('email_log_cc')),
            new GridExportColumn('bcc', $label('email_log_bcc')),
            new GridExportColumn('subject', $label('email_log_subject')),
            new GridExportColumn('params', $label('parameters')),
            new GridExportColumn('error', $label('error')),
        ];
    }

    public function countRows(GridExportQuery $query): int
    {
        return $this->createListing($query)->getTotalCount();
    }

    public function getRows(GridExportQuery $query, int $offset, int $limit): iterable
    {
        $listing = $this->createListing($query);
        $listing->setOffset($offset);
        $listing->setLimit($limit);

        $timezone = new DateTimeZone($query->timezone);

        foreach ($listing->getEmailLogs() as $log) {
            yield $this->createRow($log, $timezone);
        }
    }

    private function createListing(GridExportQuery $query): Listing
    {
        $documentId = $query->parameters['documentId'] ?? null;
        $filter = $query->parameters['filter'] ?? null;

        return $this->listingFactory->create(
            documentId: $documentId === null ? null : (int) $documentId,
            filter: $filter === null ? null : (string) $filter,
            ids: array_map(intval(...), $query->selectedIds),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function createRow(Log $log, DateTimeZone $timezone): array
    {
        return [
            'id' => $log->getId(),
            'documentId' => $log->getDocumentId(),
            'sentDate' => (new DateTimeImmutable('@' . $log->getSentDate()))->setTimezone($timezone),
            'from' => $log->getFrom(),
            'replyTo' => $log->getReplyTo(),
            'to' => $log->getTo(),
            'cc' => $log->getCc(),
            'bcc' => $log->getBcc(),
            'subject' => $log->getSubject(),
            'params' => json_encode(
                $log->getParams(),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ),
            'error' => $log->getError(),
        ];
    }
}
