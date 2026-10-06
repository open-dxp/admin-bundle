<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetUrlSlugs;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetUrlSlugsPayload implements ExtJsPayloadInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public int $objectId,
        public array $context,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            objectId: $request->query->getInt('objectId'),
            context: json_decode($request->query->getString('context'), true) ?? [],
        );
    }
}
