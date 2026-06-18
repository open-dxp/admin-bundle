<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddProperty;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class AddPropertyPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $name = '',
        public int $storeId = 0,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            name: $request->request->getString('name'),
            storeId: $request->request->getInt('storeId'),
        );
    }
}
