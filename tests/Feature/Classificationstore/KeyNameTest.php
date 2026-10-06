<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Classificationstore;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddProperty\AddPropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddProperty\AddPropertyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateProperty\UpdatePropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateProperty\UpdatePropertyPayload;
use OpenDxp\Model\DataObject\Classificationstore\KeyConfig;
use OpenDxp\Model\DataObject\Classificationstore\StoreConfig;
use OpenDxp\TestFoundation\Container;

function teststoreId(): int
{
    return StoreConfig::getByName('teststore')->getId();
}

function addKey(string $name): void
{
    Container::get(AddPropertyHandler::class)(new AddPropertyPayload($name, teststoreId()));
}

it('refuses to add a key with an invalid name', function () {
    expect(fn () => addKey('color-code'))
        ->toThrow(AdminOperationFailedException::class, 'classificationstore_invalidname');
});

it('refuses to rename a key to an invalid name', function () {
    $key = KeyConfig::getByName('input', teststoreId());
    $definition = json_decode($key->getDefinition(), true);
    $payload = new UpdatePropertyPayload(
        hasData: true,
        data: [
            'id' => $key->getId(),
            'name' => 'color-code',
            'definition' => json_encode([
                ...$definition,
                'name' => 'color-code',
            ]),
        ],
    );

    expect(fn () => Container::get(UpdatePropertyHandler::class)($payload))
        ->toThrow(AdminOperationFailedException::class, 'classificationstore_invalidname');
});
