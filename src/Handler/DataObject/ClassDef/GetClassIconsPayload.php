<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetClassIconsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public ?string $type = null,
        public ?string $classId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            type: $request->query->getString('type') ?: null,
            classId: $request->query->getString('classId') ?: null,
        );
    }
}
