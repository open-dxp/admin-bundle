<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfo;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetDeleteInfoPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $type = null,
        public readonly string $baseUrl = '',
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->get('id'),
            type: $request->query->get('type'),
            baseUrl: $request->getBaseUrl(),
        );
    }
}
