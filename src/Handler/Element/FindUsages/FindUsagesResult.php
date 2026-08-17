<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\FindUsages;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class FindUsagesResult implements ResultInterface
{
    public function __construct(
        public readonly array $data,
        public readonly int $total,
        public readonly bool $hasHidden,
    ) {}
}
