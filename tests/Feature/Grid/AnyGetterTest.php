<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Grid;

use Exception;
use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\AnyGetter;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Admin;

function anyGetterReadingTheKey(): AnyGetter
{
    return new AnyGetter((object) [
        'label' => 'Key',
        'attribute' => 'key',
        'children' => [],
    ]);
}

it('lets an admin read any getter', function () {
    $admin = UserFactory::new()
        ->admin()
        ->create();
    Admin::actingAs($admin);
    $folder = DataObjectFolderFactory::createOne(['key' => 'read-by-any-getter']);

    $labeledValue = anyGetterReadingTheKey()->getLabeledValue($folder);

    expect($labeledValue->value)->toBe('read-by-any-getter');
});

it('refuses any getter to a user who is not an admin', function () {
    $user = UserFactory::new()
        ->withPermissions('objects')
        ->create();
    Admin::actingAs($user);

    expect(fn () => anyGetterReadingTheKey())->toThrow(Exception::class, 'AnyGetter only allowed for admin users');
});
