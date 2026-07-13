<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetDependencies;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetDependenciesResult implements ResultInterface
{
    public function __construct(
        public readonly array|false $data,
    ) {}
}
