<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DocTypePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $id,
        public array $data,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $data = json_decode($request->request->get('data', ''), true) ?? [];
        $id = (int) ($data['id'] ?? 0);
        unset($data['id']);

        return new static(id: $id, data: $data);
    }
}
