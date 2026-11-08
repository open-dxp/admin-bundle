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
use OpenDxp\Tool\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Browser\KernelBrowser;

function logoFile(): string
{
    $file = sys_get_temp_dir() . '/' . uniqid('logo-', true) . '.svg';
    file_put_contents($file, '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>');

    return $file;
}

function uploadLogo(User $user, string $file): KernelBrowser
{
    return Browser::actingAs($user)->post('/admin/settings/upload-custom-logo', [
        'files' => ['Filedata' => new UploadedFile($file, 'logo.svg', 'image/svg+xml', null, true)],
    ]);
}

it('keeps a user without the appearance permission from changing the logo', function () {
    $file = logoFile();

    uploadLogo(UserFactory::createOne(), $file)->assertStatus(403);
    Browser::actingAs(UserFactory::createOne())
        ->delete('/admin/settings/delete-custom-logo')
        ->assertStatus(403);

    unlink($file);
});

it('lets a user with the appearance permission change the logo', function () {
    $file = logoFile();
    $user = UserFactory::new()
        ->withPermissions('system_appearance_settings')
        ->create();

    uploadLogo($user, $file)->assertStatus(200);
    expect(Storage::get('admin')->fileExists('custom-logo.image'))->toBeTrue();

    Browser::actingAs($user)
        ->delete('/admin/settings/delete-custom-logo')
        ->assertStatus(200);
    expect(Storage::get('admin')->fileExists('custom-logo.image'))->toBeFalse();

    unlink($file);
});
