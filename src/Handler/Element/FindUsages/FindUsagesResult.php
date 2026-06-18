<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\FindUsages;

final readonly class FindUsagesResult
{
    public function __construct(
        public readonly array $data,
        public readonly int $total,
        public readonly bool $hasHidden,
    ) {}
}
