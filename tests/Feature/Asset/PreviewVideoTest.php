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

use OpenDxp\Model\Asset\Video\Thumbnail\Config;
use OpenDxp\Test\Factory\AssetVideoFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

it('shows that the preview of a video whose conversion failed is not available', function () {
    $config = Config::getPreviewConfig()->getName();
    $video = AssetVideoFactory::createOne();
    $video->setCustomSetting('thumbnails', [$config => ['status' => 'error']]);
    $video->save();

    Browser::actingAs(UserFactory::new()->admin()->create())
        ->visit(sprintf('/admin/asset/get-preview-video?id=%d&config=%s', $video->getId(), $config))
        ->assertSuccessful()
        ->assertSee('Preview not available');
});
