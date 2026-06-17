<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\DeleteDocType;

final readonly class DeleteDocTypeResult
{
    public function __construct(
        public array $data = [],
    ) {}
}
