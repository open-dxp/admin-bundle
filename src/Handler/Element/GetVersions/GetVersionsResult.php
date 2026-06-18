<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetVersions;

final readonly class GetVersionsResult
{
    public function __construct(
        public readonly array $versions,
    ) {}
}
