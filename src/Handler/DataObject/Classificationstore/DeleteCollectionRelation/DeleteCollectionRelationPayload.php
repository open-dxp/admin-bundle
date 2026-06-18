<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteCollectionRelation;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DeleteCollectionRelationPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $colId = 0,
        public int $groupId = 0,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            colId: $request->request->getInt('colId'),
            groupId: $request->request->getInt('groupId'),
        );
    }
}
