<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\AddClass;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class AddClassPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $className = '',
        public ?string $classId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            className: $request->request->getString('className'),
            classId: $request->request->getString('classIdentifier') ?: null,
        );
    }
}
