<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogDetails;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetEmailLogDetailsResult implements ResultInterface
{
    public function __construct(
        public readonly array $objectVars,
    ) {}
}