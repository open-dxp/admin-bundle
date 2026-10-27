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

use OpenDxp\Model\Asset\Image\Thumbnail\Config;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
    $this->image = AssetImageFactory::createOne();
});

it('shows a preview that is not generated yet in the image editor', function () {
    Browser::actingAs($this->admin)
        ->visit(sprintf('/admin/asset/get-image-thumbnail?id=%d&treepreview=1', $this->image->getId()))
        ->assertSuccessful()
        ->assertHeaderContains('Content-Type', 'image/');

    expect($this->image->getThumbnail(Config::getPreviewConfig())->exists())->toBeTrue();
});

it('leaves a preview that is not generated yet to the worker in the tree', function () {
    Browser::actingAs($this->admin)
        ->visit(sprintf('/admin/asset/get-image-thumbnail?id=%d&treepreview=1&origin=treeNode', $this->image->getId()))
        ->assertStatus(404);

    expect($this->image->getThumbnail(Config::getPreviewConfig())->exists())->toBeFalse();
});
