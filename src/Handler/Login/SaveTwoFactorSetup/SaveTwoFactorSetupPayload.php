<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\SaveTwoFactorSetup;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SaveTwoFactorSetupPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $authCode,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            authCode: (string) $request->request->get('_auth_code'),
        );
    }
}
