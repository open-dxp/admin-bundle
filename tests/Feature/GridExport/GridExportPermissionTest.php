<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\GridExport;

use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Test\Factory\UserFactory;

dataset('sources and their permissions', [
    'assets' => ['assets', 'assets', ['folderId' => '1']],
    'email logs' => ['email-logs', 'emails', []],
    'objects' => [
        'objects',
        'objects',
        fn () => [
            'folderId' => '1',
            'classId' => ClassDefinition::getByName('Inheritance')->getId(),
        ],
    ],
    'translations' => ['translations', 'translations', []],
    'admin translations' => ['admin-translations', 'admin_translations', []],
]);

it('starts an export for a user with the permission of the grid', function (
    string $source,
    string $permission,
    array $parameters,
) {
    $user = UserFactory::new()
        ->withPermissions($permission)
        ->create();

    requestGridExport($user, $source, parameters: $parameters)->assertSuccessful();
})->with('sources and their permissions');

it('refuses an export to a user without the permission of the grid', function (
    string $source,
    string $permission,
    array $parameters,
) {
    $user = UserFactory::new()
        ->withPermissions('documents')
        ->create();

    requestGridExport($user, $source, parameters: $parameters)->assertStatus(403);
})->with('sources and their permissions');
