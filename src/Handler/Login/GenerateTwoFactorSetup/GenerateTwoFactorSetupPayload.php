<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\GenerateTwoFactorSetup;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GenerateTwoFactorSetupPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $error = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            error: $request->query->getString('error') ?: null,
        );
    }
}
