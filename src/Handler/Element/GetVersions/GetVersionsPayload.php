<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetVersions;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetVersionsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id = 0,
        public readonly ?string $elementType = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->getInt('id', 0),
            elementType: $request->query->get('elementType'),
        );
    }
}
