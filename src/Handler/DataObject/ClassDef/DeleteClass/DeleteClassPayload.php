<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\DeleteClass;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DeleteClassPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public ?string $id = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->request->getString('id') ?: null,
        );
    }
}
