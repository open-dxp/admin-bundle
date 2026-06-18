<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\LoginCheck;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class LoginCheckPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $perspective = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $perspective = $request->query->getString('perspective') ?: null;

        return new static(
            perspective: $perspective !== null ? strip_tags($perspective) : null,
        );
    }
}
