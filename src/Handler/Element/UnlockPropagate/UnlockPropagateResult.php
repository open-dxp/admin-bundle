<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockPropagate;

final readonly class UnlockPropagateResult
{
    public function __construct(
        public readonly bool $success,
    ) {}
}
