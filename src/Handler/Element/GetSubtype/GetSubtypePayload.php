<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetSubtype;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetSubtypePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $type = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->getString('id'),
            type: $request->query->get('type'),
        );
    }
}
