<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteTypes;

final class GetNoteTypesHandler
{
    public function __construct(
        private readonly array $documentNoteTypes,
        private readonly array $assetNoteTypes,
        private readonly array $objectNoteTypes,
    ) {}

    public function __invoke(GetNoteTypesPayload $payload): GetNoteTypesResult
    {
        $config = match ($payload->ctype) {
            'document' => $this->documentNoteTypes,
            'asset' => $this->assetNoteTypes,
            'object' => $this->objectNoteTypes,
            default => [],
        };

        $noteTypes = [];
        foreach ($config as $noteType) {
            $noteTypes[] = ['name' => $noteType];
        }

        return new GetNoteTypesResult(noteTypes: $noteTypes);
    }
}
