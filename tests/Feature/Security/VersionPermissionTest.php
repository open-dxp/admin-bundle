<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Security;

use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\User;
use OpenDxp\Model\Version;
use OpenDxp\Test\Factory\TestGuideFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

/**
 * A user who may not list the versions of an element may not delete or edit one either.
 */
function userWithVersionAccess(Concrete $object): User
{
    return UserFactory::new()
        ->withPermissions('objects')
        ->withObjectWorkspace($object, 'versions')
        ->create();
}

it('keeps a user without version access from deleting a version', function () {
    $version = TestGuideFactory::createOne()->saveVersion();

    Browser::actingAs(UserFactory::createOne())
        ->delete('/admin/element/delete-version', ['body' => ['id' => $version->getId()]])
        ->assertStatus(403);

    expect(Version::getById($version->getId()))->not->toBeNull();
});

it('deletes a version for a user with version access', function () {
    $object = TestGuideFactory::createOne();
    $version = $object->saveVersion();

    Browser::actingAs(userWithVersionAccess($object))
        ->delete('/admin/element/delete-version', ['body' => ['id' => $version->getId()]])
        ->assertStatus(200);

    expect(Version::getById($version->getId()))->toBeNull();
});

it('keeps a user without version access from deleting all versions', function () {
    $object = TestGuideFactory::createOne();
    $version = $object->saveVersion();

    Browser::actingAs(UserFactory::createOne())
        ->delete('/admin/element/delete-all-versions', [
            'body' => ['id' => $object->getId(), 'type' => 'object', 'date' => 0],
        ])
        ->assertStatus(403);

    expect(Version::getById($version->getId()))->not->toBeNull();
});

it('keeps a user without version access from editing a version', function () {
    $version = TestGuideFactory::createOne()->saveVersion();
    $version->setPublic(false);
    $version->save();

    Browser::actingAs(UserFactory::createOne())
        ->put('/admin/element/version-update', [
            'body' => ['data' => json_encode(['id' => $version->getId(), 'public' => true, 'note' => 'public'])],
        ])
        ->assertStatus(403);

    expect(Version::getById($version->getId())?->getPublic())->toBeFalse();
});

it('lets a user discard the own auto-save without version access', function () {
    $user = UserFactory::createOne();
    $version = TestGuideFactory::createOne()->saveVersion(isAutoSave: true);
    $version->setUserId($user->getId());
    $version->save();

    Browser::actingAs($user)
        ->delete('/admin/element/delete-draft', ['body' => ['id' => $version->getId()]])
        ->assertStatus(200);

    expect(Version::getById($version->getId()))->toBeNull();
});
