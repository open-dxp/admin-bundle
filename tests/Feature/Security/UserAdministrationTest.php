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

use OpenDxp\Model\User;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

it('keeps the 2FA of an admin when a user who is no admin resets it', function () {
    $admin = UserFactory::new()
        ->admin()
        ->create();
    $admin->setTwoFactorAuthentication('enabled', true);
    $admin->save();
    $manager = UserFactory::new()
        ->withPermissions('users')
        ->create();

    $browser = Browser::actingAs($manager)->put('/admin/user/reset-2fa-secret', [
        'body' => ['id' => $admin->getId()],
    ]);

    $browser->assertStatus(403);
    expect(User::getById($admin->getId(), true)?->getTwoFactorAuthentication('enabled'))->toBeTrue();
});

it('resets the 2FA of a user for a user who manages users', function () {
    $user = UserFactory::createOne();
    $user->setTwoFactorAuthentication('enabled', true);
    $user->save();
    $manager = UserFactory::new()
        ->withPermissions('users')
        ->create();

    Browser::actingAs($manager)->put('/admin/user/reset-2fa-secret', [
        'body' => ['id' => $user->getId()],
    ]);

    expect(User::getById($user->getId(), true)?->getTwoFactorAuthentication('enabled'))->toBeFalse();
});

it('keeps the own user of an admin who deletes it', function () {
    $admin = UserFactory::new()
        ->admin()
        ->create();

    $browser = Browser::actingAs($admin)->delete('/admin/user/delete', [
        'body' => ['id' => $admin->getId()],
    ]);

    $browser->assertStatus(403);
    expect(User::getById($admin->getId(), true))->not->toBeNull();
});
