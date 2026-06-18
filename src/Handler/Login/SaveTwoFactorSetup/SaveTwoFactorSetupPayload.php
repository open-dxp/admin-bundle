<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\SaveTwoFactorSetup;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SaveTwoFactorSetupPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $secret,
        public readonly string $authCode,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            secret: (string) $request->getSession()->get('2fa_secret'),
            authCode: (string) $request->request->get('_auth_code'),
        );
    }
}
