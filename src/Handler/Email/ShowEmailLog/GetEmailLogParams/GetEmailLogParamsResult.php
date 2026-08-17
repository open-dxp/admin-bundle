<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogParams;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetEmailLogParamsResult implements ResultInterface
{
    public function __construct(
        public readonly array $params,
    ) {}
}
