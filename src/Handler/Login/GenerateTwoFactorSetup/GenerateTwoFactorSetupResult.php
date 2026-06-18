<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\GenerateTwoFactorSetup;

final readonly class GenerateTwoFactorSetupResult
{
    public function __construct(
        public readonly string $secret,
        public readonly string $qrDataUri,
    ) {}
}
