<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\CreateDocType;

final readonly class CreateDocTypeResult
{
    public function __construct(
        public array $data,
    ) {}
}
