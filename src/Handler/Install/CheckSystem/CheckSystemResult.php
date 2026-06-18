<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Install\CheckSystem;

final readonly class CheckSystemResult
{
    public function __construct(
        public readonly array $viewParams,
    ) {}
}
