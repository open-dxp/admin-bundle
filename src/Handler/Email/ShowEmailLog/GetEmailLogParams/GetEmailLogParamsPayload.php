<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogParams;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetEmailLogParamsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id = 0,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: (int) $request->query->getString('id'),
        );
    }
}
