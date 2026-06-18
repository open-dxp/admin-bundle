<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\AnalyzePermissions;

final readonly class AnalyzePermissionsResult
{
    public function __construct(
        public readonly array $data,
    ) {}
}
