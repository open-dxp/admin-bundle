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

use OpenDxp\Model\DataObject\QuantityValue\Unit;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Browser\KernelBrowser;

function unitFile(): string
{
    $file = sys_get_temp_dir() . '/' . uniqid('units-', true) . '.json';
    file_put_contents($file, json_encode([[
        'id' => 'test-unit',
        'abbreviation' => 'tu',
        'longname' => 'Test Unit',
        'baseunit' => null,
        'factor' => 1,
    ]]));

    return $file;
}

function importUnits(User $user, string $file): KernelBrowser
{
    return Browser::actingAs($user)->post('/admin/quantity-value/unit-import', [
        'files' => ['Filedata' => new UploadedFile($file, 'units.json', 'application/json', null, true)],
    ]);
}

it('keeps a user without the unit permission from importing units', function () {
    $file = unitFile();

    importUnits(UserFactory::createOne(), $file)->assertStatus(403);

    expect(Unit::getById('test-unit'))->toBeNull();

    unlink($file);
});

it('imports units for a user with the unit permission', function () {
    $file = unitFile();
    $user = UserFactory::new()
        ->withPermissions('quantityValueUnits')
        ->create();

    importUnits($user, $file)->assertStatus(200);

    expect(Unit::getById('test-unit'))->not->toBeNull();

    Unit::getById('test-unit')?->delete();

    unlink($file);
});
