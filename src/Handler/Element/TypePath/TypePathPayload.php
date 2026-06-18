<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\TypePath;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class TypePathPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id = 0,
        public readonly ?string $type = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->getInt('id', 0),
            type: $request->query->get('type'),
        );
    }
}
