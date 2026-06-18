<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkImport;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final readonly class BulkImportPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $json = '',
    ) {}

    public static function fromRequest(Request $request): static
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('Filedata');

        return new static(
            json: $file !== null ? (file_get_contents($file->getPathname()) ?: '') : '',
        );
    }
}
