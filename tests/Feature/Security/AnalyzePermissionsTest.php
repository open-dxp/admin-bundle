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

it('keeps user without users permission from analyzing permissions', function () {
    Browser::actingAs(UserFactory::createOne())
        ->post('/admin/element/analyze-permissions', [
            'body' => ['elementType' => 'document', 'elementId' => 1],
        ])
        ->assertStatus(403);
});

it('lets user with users permission analyze permissions', function () {
    $manager = UserFactory::new()
        ->withPermissions('users')
        ->create();

    Browser::actingAs($manager)
        ->post('/admin/element/analyze-permissions', [
            'body' => ['elementType' => 'document', 'elementId' => 1],
        ])
        ->assertStatus(200);
});
