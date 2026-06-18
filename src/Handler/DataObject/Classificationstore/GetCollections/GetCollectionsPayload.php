<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetCollections;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetCollectionsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public array $queryAll = [],
        public int $limit = 15,
        public int $start = 0,
        public ?string $dir = null,
        public bool $overrideSort = false,
        public ?int $oid = null,
        public ?string $fieldname = null,
        public ?string $searchfilter = null,
        public ?int $storeId = null,
        public ?string $filter = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $rawLimit = $request->query->get('limit');

        return new static(
            queryAll: $request->query->all(),
            limit: $rawLimit !== null ? (int) $rawLimit : 15,
            start: $request->query->getInt('start'),
            dir: $request->query->getString('dir') ?: null,
            overrideSort: (bool) $request->query->get('overrideSort'),
            oid: ($v = $request->query->get('oid')) !== null && is_numeric($v) ? (int) $v : null,
            fieldname: $request->query->getString('fieldname') ?: null,
            searchfilter: $request->query->getString('searchfilter') ?: null,
            storeId: ($v = $request->query->get('storeId')) !== null && is_numeric($v) ? (int) $v : null,
            filter: $request->query->getString('filter') ?: null,
        );
    }
}
