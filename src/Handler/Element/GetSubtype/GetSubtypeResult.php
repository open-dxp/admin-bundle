<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetSubtype;

final readonly class GetSubtypeResult
{
    public function __construct(
        public readonly ?string $subtype,
        public readonly int $id,
        public readonly ?string $type,
    ) {}
}
