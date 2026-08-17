<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Install\CheckSystem;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class CheckSystemResult implements ResultInterface
{
    public function __construct(
        public readonly array $viewParams,
    ) {}
}
