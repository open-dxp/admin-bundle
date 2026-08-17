<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkCommit;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class BulkCommitPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public array $data = [],
    ) {}

    public static function fromRequest(Request $request): static
    {
        $data = json_decode($request->request->getString('data'), true);

        return new static(
            data: is_array($data) ? $data : [],
        );
    }
}
