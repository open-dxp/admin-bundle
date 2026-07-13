<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\GetEmailLogs;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetEmailLogsResult implements ResultInterface
{
    public function __construct(
        public readonly array $data,
        public readonly int $total,
    ) {}
}
