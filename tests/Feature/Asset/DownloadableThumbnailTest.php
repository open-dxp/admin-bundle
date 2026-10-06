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
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\ThumbnailConfigFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
    $this->folder = AssetFolderFactory::createOne();
    $this->image = AssetImageFactory::new()
        ->withParent($this->folder)
        ->create();
});

dataset('downloads naming a thumbnail', [
    'the thumbnail of an image' => fn (): string => sprintf(
        '/admin/asset/download-image-thumbnail?id=%d',
        $this->image->getId(),
    ),
    'the jobs of a ZIP download' => fn (): string => sprintf(
        '/admin/asset/download-as-zip-jobs?id=%d',
        $this->folder->getId(),
    ),
    'a job of a ZIP download' => fn (): string => sprintf(
        '/admin/asset/download-as-zip-add-files?id=%d&jobId=%s&limit=5',
        $this->folder->getId(),
        uniqid(),
    ),
]);

it('hands out a thumbnail that is offered for download', function (string $download) {
    $thumbnail = ThumbnailConfigFactory::new()
        ->scalingByWidth(40)
        ->with(['downloadable' => true])
        ->create();

    Browser::actingAs($this->admin)
        ->visit(sprintf('%s&thumbnail=%s', $download, $thumbnail->getName()))
        ->assertSuccessful();
})->with('downloads naming a thumbnail');

it('refuses a thumbnail that is not offered for download', function (string $download) {
    $thumbnail = ThumbnailConfigFactory::new()
        ->scalingByWidth(40)
        ->with(['downloadable' => false])
        ->create();

    Browser::actingAs($this->admin)
        ->visit(sprintf('%s&thumbnail=%s', $download, $thumbnail->getName()))
        ->assertStatus(400);
})->with('downloads naming a thumbnail');
