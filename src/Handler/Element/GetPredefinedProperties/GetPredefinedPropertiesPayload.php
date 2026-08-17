<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetPredefinedProperties;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetPredefinedPropertiesPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $elementType = null,
        public readonly ?string $query = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            elementType: $request->query->get('elementType'),
            query: $request->query->get('query'),
        );
    }
}
