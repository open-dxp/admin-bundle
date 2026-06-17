<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DeleteGroupPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $id = 0,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->request->getInt('id'),
        );
    }
}
