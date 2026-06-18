<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElement;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UnlockElementPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->request->getInt('id'),
            type: $request->request->getString('type'),
        );
    }
}
