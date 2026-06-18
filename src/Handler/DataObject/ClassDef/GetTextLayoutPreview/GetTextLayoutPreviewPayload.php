<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetTextLayoutPreview;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetTextLayoutPreviewPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $objPath = '',
        public ?string $className = null,
        public ?string $renderingData = null,
        public ?string $renderingClass = null,
        public ?string $html = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            objPath: $request->query->getString('previewObject'),
            className: $request->query->getString('className') ?: null,
            renderingData: $request->query->getString('renderingData') ?: null,
            renderingClass: $request->query->getString('renderingClass') ?: null,
            html: $request->query->getString('html') ?: null,
        );
    }
}
