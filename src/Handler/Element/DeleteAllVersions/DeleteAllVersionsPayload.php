<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteAllVersions;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DeleteAllVersionsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $date = null,
        public readonly ?string $type = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->request->getInt('id'),
            date: $request->request->get('date'),
            type: $request->request->get('type'),
        );
    }
}
