<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog;

final readonly class GetEmailLogResult
{
    public function __construct(
        public readonly ?string $textLog,
        public readonly ?string $htmlLog,
        public readonly array $objectVars,
    ) {}
}
