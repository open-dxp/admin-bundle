<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNicePath;

final readonly class GetNicePathResult
{
    public function __construct(
        public readonly array $data,
    ) {}
}
