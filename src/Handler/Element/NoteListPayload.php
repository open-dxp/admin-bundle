<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NoteListPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly bool $hasData,
        public readonly int $id = 0,
        public readonly int $offset = 0,
        public readonly ?int $limit = null,
        public readonly array $sortingSettings = [],
        public readonly ?string $filterText = null,
        public readonly ?string $filterJson = null,
        public readonly ?string $cid = null,
        public readonly ?string $ctype = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        if ($request->request->has('data')) {
            $data = json_decode($request->request->getString('data'), true) ?? [];

            return new static(
                hasData: true,
                id: (int) ($data['id'] ?? 0),
            );
        }

        $filterText = $request->request->get('filterText');
        $filterJson = $request->request->get('filter');

        return new static(
            hasData: false,
            offset: $request->request->getInt('start', 0),
            limit: $request->request->getInt('limit') ?: null,
            sortingSettings: QueryParams::extractSortingSettings($request->request->all()),
            filterText: $filterText !== null ? (string) $filterText : null,
            filterJson: $filterJson !== null ? (string) $filterJson : null,
            cid: $request->request->has('cid') ? $request->request->getString('cid') : null,
            ctype: $request->request->has('ctype') ? $request->request->getString('ctype') : null,
        );
    }
}
