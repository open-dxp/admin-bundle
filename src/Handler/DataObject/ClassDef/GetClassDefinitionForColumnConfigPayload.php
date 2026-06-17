<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetClassDefinitionForColumnConfigPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public ?string $id = null,
        public int $objectId = 0,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->getString('id') ?: null,
            objectId: $request->query->getInt('oid'),
        );
    }
}
