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

use OpenDxp\Model\Element\Editlock;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

it('releases the edit locks of the session when the user logs out', function () {
    $browser = Browser::actingAs(UserFactory::createOne())
        ->put('/admin/element/lock-element', ['body' => ['id' => 1, 'type' => 'document']])
        ->assertSuccessful();

    expect(Editlock::getByElement(1, 'document'))->not->toBeNull();

    $browser->post('/admin/logout');

    expect(Editlock::getByElement(1, 'document'))->toBeNull();
});
