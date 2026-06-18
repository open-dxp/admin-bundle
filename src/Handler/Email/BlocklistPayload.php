<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class BlocklistPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly bool $hasData,
        public readonly array $data = [],
        public readonly int $limit = 50,
        public readonly int $offset = 0,
        public readonly array $sortingSettings = [],
        public readonly ?string $filter = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        if ($request->request->has('data')) {
            $data = json_decode($request->request->getString('data'), true) ?? [];

            if (is_array($data)) {
                foreach ($data as $key => &$value) {
                    if (is_string($value)) {
                        if ($key === 'address') {
                            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                        }
                        $value = trim($value);
                    }
                }
                unset($value);
            }

            return new static(hasData: true, data: $data);
        }

        return new static(
            hasData: false,
            limit: $request->request->getInt('limit', 50),
            offset: $request->request->getInt('start', 0),
            sortingSettings: QueryParams::extractSortingSettings($request->request->all()),
            filter: $request->request->has('filter') ? $request->request->getString('filter') : null,
        );
    }
}
