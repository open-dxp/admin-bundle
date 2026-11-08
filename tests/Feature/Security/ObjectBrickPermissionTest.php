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

use OpenDxp\Model\DataObject\Objectbrick\Definition;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use Zenstruck\Browser\KernelBrowser;

/**
 * @param array<string, mixed> $body
 */
function saveObjectBrick(User $user, array $body): KernelBrowser
{
    return Browser::actingAs($user)->put('/admin/class/objectbrick-update', [
        'body' => $body,
    ]);
}

it('keeps a user without the objectbricks permission from creating a brick', function () {
    $user = UserFactory::createOne();

    saveObjectBrick($user, [
        'key' => 'deniedBrick',
        'title' => 'Denied Brick',
        'group' => 'Denied',
        'task' => 'add',
    ])->assertStatus(403);

    expect(Definition::getByKey('deniedBrick'))->toBeNull();
});

it('creates a brick for a user with the objectbricks permission', function () {
    $user = UserFactory::new()
        ->withPermissions('objectbricks')
        ->create();

    saveObjectBrick($user, [
        'key' => 'allowedBrick',
        'title' => 'Allowed Brick',
        'group' => 'Allowed',
        'task' => 'add',
    ])->assertStatus(200);

    expect(Definition::getByKey('allowedBrick'))->not->toBeNull();

    Definition::getByKey('allowedBrick')?->delete();
});
