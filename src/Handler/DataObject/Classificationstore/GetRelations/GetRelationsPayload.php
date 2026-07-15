<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetRelations;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetRelationsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public array $queryAll = [],
        public ?string $relationIds = null,
        public int $limit = 15,
        public int $start = 0,
        public ?string $dir = null,
        public bool $overrideSort = false,
        public ?string $filter = null,
        public ?string $groupId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $rawLimit = $request->query->getInt('limit');

        return new static(
            queryAll: $request->query->all(),
            relationIds: $request->query->getString('relationIds') ?: null,
            limit: $rawLimit ?: 15,
            start: $request->query->getInt('start'),
            dir: $request->query->getString('dir') ?: null,
            overrideSort: $request->query->getBoolean('overrideSort'),
            filter: $request->query->getString('filter') ?: null,
            groupId: $request->query->getString('groupId') ?: null,
        );
    }
}
