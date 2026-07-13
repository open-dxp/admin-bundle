<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteTypes;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetNoteTypesResult implements ResultInterface
{
    public function __construct(
        public readonly array $noteTypes,
    ) {}
}
