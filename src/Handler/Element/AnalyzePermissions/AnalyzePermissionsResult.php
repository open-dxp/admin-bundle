<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\AnalyzePermissions;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class AnalyzePermissionsResult implements ResultInterface
{
    public function __construct(
        public readonly array $data,
    ) {}
}
