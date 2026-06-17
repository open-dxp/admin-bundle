<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Model\DataObject\SelectOptions\Config;
use Symfony\Component\HttpFoundation\Request;

final readonly class DeleteSelectOptionsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public ?string $id = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->request->getString(Config::PROPERTY_ID) ?: null,
        );
    }
}
