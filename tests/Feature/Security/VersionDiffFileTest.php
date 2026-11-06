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

use const OPENDXP_SYSTEM_TEMP_DIRECTORY;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

function tempDirectory(): string
{
    $directory = OPENDXP_SYSTEM_TEMP_DIRECTORY;
    if (!is_dir($directory)) {
        mkdir($directory, 0o777, true);
    }

    return $directory;
}

it('does not serve another file from the temporary directory', function () {
    $file = tempDirectory() . '/' . uniqid('stray-', true) . '.html';
    file_put_contents($file, 'stray');

    Browser::actingAs(UserFactory::createOne())
        ->visit('/__admin/document/diff-versions-html?id=' . basename($file))
        ->assertStatus(404);

    unlink($file);
});

it('serves a generated version diff file', function () {
    $name = 'version-diff-tmp-' . uniqid('', true) . '-from.html';
    file_put_contents(tempDirectory() . '/' . $name, 'diff');

    Browser::actingAs(UserFactory::createOne())
        ->visit('/__admin/document/diff-versions-html?id=' . $name)
        ->assertStatus(200);

    unlink(tempDirectory() . '/' . $name);
});
