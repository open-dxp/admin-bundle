<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetPredefinedProperties;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetPredefinedPropertiesResult implements ResultInterface
{
    public function __construct(
        public readonly array $properties,
    ) {}
}
