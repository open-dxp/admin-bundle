<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveClassDefinition;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SaveClassDefinitionPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $id = '',
        public array $configuration = [],
        public array $values = [],
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->request->getString('id'),
            configuration: json_decode($request->request->getString('configuration'), true) ?? [],
            values: json_decode($request->request->getString('values'), true) ?? [],
        );
    }
}
