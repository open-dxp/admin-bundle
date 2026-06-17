<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetSelectOptionsTreePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $grouped = 0,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            grouped: $request->query->getInt('grouped'),
        );
    }
}
