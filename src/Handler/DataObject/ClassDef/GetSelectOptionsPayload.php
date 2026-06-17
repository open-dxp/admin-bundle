<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Model\DataObject\SelectOptions\Config;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetSelectOptionsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public ?string $id = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->query->getString(Config::PROPERTY_ID) ?: null,
        );
    }
}
