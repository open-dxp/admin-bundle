<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetPredefinedProperties;

final readonly class GetPredefinedPropertiesResult
{
    public function __construct(
        public readonly array $properties,
    ) {}
}
