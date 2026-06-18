<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkExportPrepare;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class BulkExportPreparePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $data = '',
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            data: $request->request->getString('data'),
        );
    }
}
