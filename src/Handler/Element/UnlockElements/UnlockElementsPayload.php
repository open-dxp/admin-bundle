<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElements;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UnlockElementsPayload implements ExtJsPayloadInterface
{
    /** @param array<array{id: int|string, type: string}> $elements */
    public function __construct(
        public readonly array $elements,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $body = json_decode($request->getContent(), true) ?? [];

        return new static(
            elements: $body['elements'] ?? [],
        );
    }
}
