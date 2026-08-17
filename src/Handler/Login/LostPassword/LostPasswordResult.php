<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class LostPasswordResult implements ResultInterface
{
    public function __construct(
        public readonly ?string $error,
        public readonly ?Response $eventResponse = null,
    ) {}
}
