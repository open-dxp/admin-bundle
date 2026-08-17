<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteList;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetNoteListResult implements ResultInterface
{
    public function __construct(
        public array $data,
        public int $total,
    ) {}
}
