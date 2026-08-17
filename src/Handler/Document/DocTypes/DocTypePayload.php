<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DocTypePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $id,
        public array $data,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $data = json_decode($request->request->getString('data'), true) ?? [];
        $id = (string) ($data['id'] ?? '');
        unset($data['id']);

        return new static(id: $id, data: $data);
    }
}
