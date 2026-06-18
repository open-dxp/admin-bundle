<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\TypePath;

final readonly class TypePathResult
{
    public function __construct(
        public readonly int $index,
        public readonly string $idPath,
        public readonly string $typePath,
        public readonly string $fullpath,
        public readonly ?string $sortIndexPath,
    ) {}
}
