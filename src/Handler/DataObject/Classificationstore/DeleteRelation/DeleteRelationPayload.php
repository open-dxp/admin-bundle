<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\DeleteRelation;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DeleteRelationPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $keyId = 0,
        public int $groupId = 0,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            keyId: $request->request->getInt('keyId'),
            groupId: $request->request->getInt('groupId'),
        );
    }
}
