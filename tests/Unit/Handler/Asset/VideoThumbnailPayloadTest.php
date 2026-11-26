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

namespace OpenDxp\Bundle\AdminBundle\Tests\Unit\Handler\Asset;

use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetVideoThumbnail\GetVideoThumbnailPayload;
use Symfony\Component\HttpFoundation\Request;

it('reads the time of the player position, 0 seconds included', function (string $time) {
    $request = Request::create(
        '/admin/asset/get-video-thumbnail',
        'GET',
        [
            'id' => '42',
            'time' => $time,
            'settime' => 'true',
        ],
    );

    expect(GetVideoThumbnailPayload::fromRequest($request)->time)->toBe($time);
})->with([
    'the start of the video' => ['0'],
    'a fraction of a second' => ['0.4'],
    'later in the video' => ['12.9'],
]);

it('reads a missing time as null', function () {
    $request = Request::create('/admin/asset/get-video-thumbnail', 'GET', ['id' => '42']);

    expect(GetVideoThumbnailPayload::fromRequest($request)->time)->toBeNull();
});
