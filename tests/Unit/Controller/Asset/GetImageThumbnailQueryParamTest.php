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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Tests\Unit\Controller\Asset;

use OpenDxp\Bundle\AdminBundle\Tests\Support\Test\UnitTestCase;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Covers the InputBag workaround in AssetController::getImageThumbnailAction().
 *
 * InputBag::get() rejects non-scalar values (Symfony 5.1+), but the `thumbnail`
 * param can legitimately be an array (inline config) or a string (named config).
 * The fix reads via ->all()['thumbnail'] to bypass the scalar-only restriction.
 */
class GetImageThumbnailQueryParamTest extends UnitTestCase
{
    public function testGetThrowsForArrayThumbnailParam(): void
    {
        $request = Request::create('/get-image-thumbnail', 'GET', [
            'thumbnail' => ['width' => 100, 'height' => 200],
        ]);

        $this->expectException(BadRequestException::class);

        $request->query->get('thumbnail');
    }

    public function testAllReturnsArrayThumbnailParam(): void
    {
        $request = Request::create('/get-image-thumbnail', 'GET', [
            'thumbnail' => ['width' => 100, 'height' => 200],
        ]);

        $thumbnailParam = $request->query->all()['thumbnail'] ?? null;

        self::assertSame(['width' => 100, 'height' => 200], $thumbnailParam);
    }

    public function testAllReturnsStringThumbnailParam(): void
    {
        $request = Request::create('/get-image-thumbnail', 'GET', [
            'thumbnail' => 'my-thumbnail-config',
        ]);

        $thumbnailParam = $request->query->all()['thumbnail'] ?? null;

        self::assertSame('my-thumbnail-config', $thumbnailParam);
    }

    public function testAbsentThumbnailParamYieldsNull(): void
    {
        $request = Request::create('/get-image-thumbnail', 'GET', ['id' => '42']);

        $thumbnailParam = $request->query->all()['thumbnail'] ?? null;

        self::assertNull($thumbnailParam);
    }
}
