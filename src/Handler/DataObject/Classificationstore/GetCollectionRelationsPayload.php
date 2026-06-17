<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetCollectionRelationsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public array $queryAll = [],
        public int $limit = 15,
        public int $start = 0,
        public ?string $dir = null,
        public bool $overrideSort = false,
        public ?string $filter = null,
        public int $colId = 0,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $rawLimit = $request->query->getInt('limit');

        return new static(
            queryAll: $request->query->all(),
            limit: $rawLimit ?: 15,
            start: $request->query->getInt('start'),
            dir: $request->query->getString('dir') ?: null,
            overrideSort: (bool) $request->query->get('overrideSort'),
            filter: $request->query->getString('filter') ?: null,
            colId: $request->query->getInt('colId'),
        );
    }
}
