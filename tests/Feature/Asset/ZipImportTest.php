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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Asset;

use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use ZipArchive;

it('imports only a zip of the temporary directory', function () {
    $outside = dirname(OPENDXP_SYSTEM_TEMP_DIRECTORY) . '/zip-import-outside.zip';
    $zip = new ZipArchive();
    $zip->open($outside, ZipArchive::CREATE);
    $zip->addFromString('outside.txt', 'outside');
    $zip->close();

    Browser::actingAs(UserFactory::new()->admin()->create())
        ->post('/admin/asset/import-zip-files', ['body' => [
            'parentId' => AssetFolderFactory::createOne()->getId(),
            'jobId' => '../zip-import-outside',
            'offset' => 0,
            'limit' => 10,
            'isLast' => 1,
        ]])
        ->assertStatus(400);

    expect(is_file($outside))->toBeTrue();

    unlink($outside);
});
