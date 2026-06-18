<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Install\CheckSystem;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class CheckSystemPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly bool $headless,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            headless: $request->query->getBoolean('headless') || $request->request->getBoolean('headless'),
        );
    }
}
