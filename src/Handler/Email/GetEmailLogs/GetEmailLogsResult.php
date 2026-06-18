<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\GetEmailLogs;

final readonly class GetEmailLogsResult
{
    public function __construct(
        public readonly array $data,
        public readonly int $total,
    ) {}
}
