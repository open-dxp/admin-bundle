<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Install\CheckSystem;

use Doctrine\DBAL\Connection;
use OpenDxp\Tool\Requirements;

final class CheckSystemHandler
{
    public function __construct(
        private readonly Connection $db,
    ) {}

    public function __invoke(CheckSystemPayload $payload): CheckSystemResult
    {
        $viewParams = Requirements::checkAll($this->db);
        $viewParams['headless'] = $payload->headless;

        return new CheckSystemResult(viewParams: $viewParams);
    }
}
