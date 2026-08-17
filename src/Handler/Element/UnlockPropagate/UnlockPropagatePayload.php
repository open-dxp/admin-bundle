<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockPropagate;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UnlockPropagatePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $type,
        public readonly int $id,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            type: $request->request->get('type'),
            id: $request->request->getInt('id'),
        );
    }
}
