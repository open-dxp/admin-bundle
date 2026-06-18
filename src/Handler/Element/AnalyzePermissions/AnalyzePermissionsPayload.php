<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\AnalyzePermissions;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class AnalyzePermissionsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $elementId,
        public readonly ?int $userId = null,
        public readonly ?string $elementType = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            userId: $request->request->getInt('userId') ?: null,
            elementType: $request->request->get('elementType'),
            elementId: $request->request->getInt('elementId'),
        );
    }
}
