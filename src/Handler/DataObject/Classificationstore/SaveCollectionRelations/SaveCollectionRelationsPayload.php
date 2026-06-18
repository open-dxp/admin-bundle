<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SaveCollectionRelations;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SaveCollectionRelationsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public bool $hasData = false,
        public array $data = [],
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            hasData: $request->request->has('data'),
            data: json_decode($request->request->getString('data'), true) ?? [],
        );
    }
}
