<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetProperties;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetPropertiesPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public array $queryAll = [],
        public int $storeId = 0,
        public ?string $frameName = null,
        public int $limit = 15,
        public int $start = 0,
        public ?string $dir = null,
        public bool $overrideSort = false,
        public ?string $groupIds = null,
        public ?string $keyIds = null,
        public ?string $searchfilter = null,
        public ?string $filter = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $rawLimit = $request->query->getInt('limit');

        return new static(
            queryAll: $request->query->all(),
            storeId: $request->query->getInt('storeId'),
            frameName: $request->query->getString('frameName') ?: null,
            limit: $rawLimit ?: 15,
            start: $request->query->getInt('start'),
            dir: $request->query->getString('dir') ?: null,
            overrideSort: (bool) $request->query->get('overrideSort'),
            groupIds: $request->query->getString('groupIds') ?: null,
            keyIds: $request->query->getString('keyIds') ?: null,
            searchfilter: $request->query->getString('searchfilter') ?: null,
            filter: $request->query->getString('filter') ?: null,
        );
    }
}
