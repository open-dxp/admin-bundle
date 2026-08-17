<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\AddDocument;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class AddDocumentPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $parentId,
        public string $type,
        public string $key,
        public ?string $docTypeId,
        public ?string $translationsBaseDocumentId,
        public ?string $language,
        public ?string $inheritanceSource,
        public ?string $title,
        public ?string $name,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            parentId: $request->request->getInt('parentId'),
            type: $request->request->getString('type'),
            key: $request->request->getString('key'),
            docTypeId: $request->request->getString('docTypeId') ?: null,
            translationsBaseDocumentId: $request->request->getString('translationsBaseDocument') ?: null,
            language: $request->request->getString('language') ?: null,
            inheritanceSource: $request->request->has('inheritanceSource') ? $request->request->getString('inheritanceSource') : null,
            title: $request->request->getString('title') ?: null,
            name: $request->request->getString('name') ?: null,
        );
    }
}
