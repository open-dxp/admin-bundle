<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class AddGroupsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public array $ids = [],
        public int $oid = 0,
        public ?string $fieldname = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            ids: json_decode($request->request->getString('groupIds'), true) ?: [],
            oid: $request->request->getInt('oid'),
            fieldname: $request->request->getString('fieldname') ?: null,
        );
    }
}
