<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\UpdateDocType;

final readonly class UpdateDocTypeResult
{
    public function __construct(
        public array $data,
    ) {}
}
