<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\GetEmailLogs;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetEmailLogsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?int $documentId = null,
        public readonly int $limit = 50,
        public readonly int $start = 0,
        public readonly ?string $filter = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            documentId: $request->request->has('documentId') ? (int) $request->request->get('documentId') : null,
            limit: (int) $request->request->get('limit', 50),
            start: (int) $request->request->get('start', 0),
            filter: $request->request->has('filter') ? $request->request->get('filter') : null,
        );
    }
}
