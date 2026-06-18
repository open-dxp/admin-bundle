<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ShowEmailLogPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $type = null,
        public readonly int $id = 0,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            type: $request->query->get('type'),
            id: $request->query->getInt('id', 0),
        );
    }
}
