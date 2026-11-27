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

use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

it('hands out default key bindings to user without users permission', function () {
    Browser::actingAs(UserFactory::createOne())
        ->get('/admin/user/get-default-key-bindings')
        ->assertStatus(200);
});

it('returns default key bindings of edited user without own key bindings', function () {
    $manager = UserFactory::new()
        ->withPermissions('users')
        ->create();
    $user = UserFactory::createOne();

    $content = Browser::actingAs($manager)
        ->get('/admin/user/get', ['query' => ['id' => $user->getId()]])
        ->assertStatus(200)
        ->content();
    $keyBindings = json_decode(json_decode($content, true)['user']['keyBindings'], true);

    expect(array_column($keyBindings, 'action'))->toContain('save');
});
