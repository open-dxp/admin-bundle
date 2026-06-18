<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteTypes;

final readonly class GetNoteTypesResult
{
    public function __construct(
        public readonly array $noteTypes,
    ) {}
}
