<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetDependenciesPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id = 0,
        public readonly ?string $type = null,
        public readonly int $start = 0,
        public readonly int $limit = 25,
        public readonly ?string $filter = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->getInt('id', 0),
            type: $request->query->get('elementType'),
            start: $request->query->getInt('start', 0),
            limit: $request->query->getInt('limit', 25),
            filter: $request->query->get('filter'),
        );
    }
}
