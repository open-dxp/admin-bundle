<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword;

use Symfony\Component\HttpFoundation\Response;

final readonly class LostPasswordResult
{
    public function __construct(
        public readonly ?string $error,
        public readonly ?Response $eventResponse = null,
    ) {}
}
