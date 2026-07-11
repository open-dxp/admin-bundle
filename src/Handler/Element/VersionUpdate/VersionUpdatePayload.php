<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\VersionUpdate;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class VersionUpdatePayload implements ExtJsPayloadInterface
{
    /** @var array<string, mixed>|null */
    public readonly ?array $data;

    public function __construct(?array $data)
    {
        $this->data = $data;
    }

    public static function fromRequest(Request $request): static
    {
        $data = $request->request->get('data');

        return new static(
            data: is_string($data)
                ? (json_decode($data, true) ?? null)
                : null,
        );
    }
}
