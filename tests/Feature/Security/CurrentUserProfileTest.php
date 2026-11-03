<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Security;

use OpenDxp\Model\User;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\Tool\Authentication;
use Zenstruck\Browser\KernelBrowser;

/**
 * @param array<string, mixed> $values
 */
function saveProfile(User $user, array $values): KernelBrowser
{
    return Browser::actingAs($user)->put('/admin/user/update-current-user', [
        'body' => [
            'id' => $user->getId(),
            'data' => json_encode($values),
        ],
    ]);
}

it('saves the fields of the profile', function () {
    $user = UserFactory::createOne();

    saveProfile($user, [
        'firstname' => 'Anna',
        'lastname' => 'Berger',
        'email' => 'anna@example.org',
        'language' => 'de',
    ]);

    expect(User::getById($user->getId(), true))
        ->getFirstname()
        ->toBe('Anna')
        ->getLastname()
        ->toBe('Berger')
        ->getEmail()
        ->toBe('anna@example.org')
        ->getLanguage()
        ->toBe('de');
});

it('changes the password when the old password is right', function () {
    $user = UserFactory::new()
        ->withPassword('old-secret-password')
        ->create();

    saveProfile($user, [
        'old_password' => 'old-secret-password',
        'new_password' => 'new-secret-password',
        'retype_password' => 'new-secret-password',
    ]);

    expect(Authentication::verifyPassword(User::getById($user->getId(), true), 'new-secret-password'))->toBeTrue();
});

it('keeps every field of the user outside the profile', function () {
    $folder = User\Folder::create([
        'parentId' => 0,
        'name' => 'Editors',
    ]);
    $user = UserFactory::createOne([
        'parentId' => $folder->getId(),
        'provider' => 'external:anna',
    ]);

    saveProfile($user, [
        'Admin' => true,
        'Permissions' => 'users',
        'Roles' => '1',
        'Active' => false,
        'provider' => '',
        'parentId' => 0,
        'classes' => 'all',
    ]);

    expect(User::getById($user->getId(), true))
        ->isAdmin()
        ->toBeFalse()
        ->getPermissions()
        ->not->toContain('users')
        ->getRoles()
        ->not->toContain(1)
        ->isActive()
        ->toBeTrue()
        ->getProvider()
        ->toBe('external:anna')
        ->getParentId()
        ->toBe($folder->getId())
        ->getClasses()
        ->not->toContain('all');
});
