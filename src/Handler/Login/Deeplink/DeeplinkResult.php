<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\Deeplink;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class DeeplinkResult implements ResultInterface
{
    public function __construct(
        public readonly ?string $redirectUrl = null,
        public readonly ?string $template = null,
        public readonly array $params = [],
    ) {}
}
