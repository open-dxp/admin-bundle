<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\GetBlocklist;

final readonly class GetBlocklistResult
{
    public function __construct(
        public readonly array $data,
        public readonly int $total,
    ) {}
}
