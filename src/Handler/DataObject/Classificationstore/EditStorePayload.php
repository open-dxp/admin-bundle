<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class EditStorePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $id = 0,
        public string $name = '',
        public ?string $description = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $data = json_decode($request->request->getString('data'), true) ?? [];

        return new static(
            id: $request->request->getInt('id'),
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
        );
    }
}
