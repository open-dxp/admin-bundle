<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetEmailLogResult implements ResultInterface
{
    public function __construct(
        public readonly ?string $textLog,
        public readonly ?string $htmlLog,
        public readonly array $objectVars,
    ) {}
}
