<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogText;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetEmailLogTextResult implements ResultInterface
{
    public function __construct(
        public readonly ?string $textLog,
    ) {}
}