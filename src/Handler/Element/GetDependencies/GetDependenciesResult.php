<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetDependencies;

final readonly class GetDependenciesResult
{
    public function __construct(
        public readonly array|false $data,
    ) {}
}
