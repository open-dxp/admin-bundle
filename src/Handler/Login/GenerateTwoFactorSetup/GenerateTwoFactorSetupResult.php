<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\GenerateTwoFactorSetup;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GenerateTwoFactorSetupResult implements ResultInterface
{
    public function __construct(
        public readonly string $secret,
        public readonly string $qrDataUri,
    ) {}
}
