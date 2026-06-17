<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Model\DataObject\SelectOptions\Config;
use Symfony\Component\HttpFoundation\Request;

final readonly class SaveSelectOptionsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $id = '',
        public string $task = '',
        public ?string $group = null,
        public string $useTraits = '',
        public string $implementsInterfaces = '',
        public ?array $selectOptionsData = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $rawSelectOptions = $request->request->getString(Config::PROPERTY_SELECT_OPTIONS);

        return new static(
            id: $request->request->getString(Config::PROPERTY_ID),
            task: $request->request->getString('task'),
            group: $request->request->getString(Config::PROPERTY_GROUP) ?: null,
            useTraits: $request->request->getString(Config::PROPERTY_USE_TRAITS),
            implementsInterfaces: $request->request->getString(Config::PROPERTY_IMPLEMENTS_INTERFACES),
            selectOptionsData: $rawSelectOptions !== '' ? json_decode($rawSelectOptions, true) : null,
        );
    }
}
