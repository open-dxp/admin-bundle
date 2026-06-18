<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteList;

final readonly class GetNoteListResult
{
    public function __construct(
        public array $data,
        public int $total,
    ) {}
}
