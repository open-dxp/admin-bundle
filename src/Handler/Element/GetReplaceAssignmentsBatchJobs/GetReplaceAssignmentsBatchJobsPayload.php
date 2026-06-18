<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetReplaceAssignmentsBatchJobs;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetReplaceAssignmentsBatchJobsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $type = null,
        public readonly ?string $path = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->getInt('id') ?: null,
            type: $request->query->get('type'),
            path: $request->query->get('path'),
        );
    }
}
