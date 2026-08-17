<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteTypes;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetNoteTypesPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $ctype = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            ctype: $request->query->get('ctype'),
        );
    }
}
