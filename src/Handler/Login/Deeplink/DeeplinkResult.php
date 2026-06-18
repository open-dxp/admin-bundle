<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\Deeplink;

final readonly class DeeplinkResult
{
    public function __construct(
        public readonly ?string $redirectUrl = null,
        public readonly ?string $template = null,
        public readonly array $params = [],
    ) {}
}
