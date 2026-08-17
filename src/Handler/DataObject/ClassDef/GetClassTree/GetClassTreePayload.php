<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassTree;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetClassTreePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public bool $createAllowed = false,
        public bool $withId = false,
        public bool $useTitle = false,
        public bool $grouped = false,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            createAllowed: (bool) $request->query->get('createAllowed'),
            withId: (bool) $request->query->get('withId'),
            useTitle: (bool) $request->query->get('useTitle'),
            grouped: (bool) $request->query->get('grouped'),
        );
    }
}
