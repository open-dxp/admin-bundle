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

namespace OpenDxp\Bundle\AdminBundle\Tests\Unit\Handler\DataObject\ClassDef\GetClassDefinitionForColumnConfig;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassDefinitionForColumnConfig\GetClassDefinitionForColumnConfigPayload;
use OpenDxp\Bundle\AdminBundle\Tests\Support\Test\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

class GetClassDefinitionForColumnConfigPayloadTest extends UnitTestCase
{
    private static function payloadFor(array $query): GetClassDefinitionForColumnConfigPayload
    {
        $request = Request::create('/admin/class/get-class-definition-for-column-config', 'GET', $query);

        return GetClassDefinitionForColumnConfigPayload::fromRequest($request);
    }

    public function testAbsentOidYieldsZero(): void
    {
        self::assertSame(0, self::payloadFor(['id' => 'news'])->objectId);
    }

    public function testEmptyOidYieldsZero(): void
    {
        self::assertSame(0, self::payloadFor(['id' => 'news', 'oid' => ''])->objectId);
    }

    public function testUndefinedStringOidYieldsZero(): void
    {
        self::assertSame(0, self::payloadFor(['id' => 'news', 'oid' => 'undefined'])->objectId);
    }

    public function testNullStringOidYieldsZero(): void
    {
        self::assertSame(0, self::payloadFor(['id' => 'news', 'oid' => 'null'])->objectId);
    }

    public function testNumericOidIsParsed(): void
    {
        self::assertSame(42, self::payloadFor(['id' => 'news', 'oid' => '42'])->objectId);
    }

    public function testZeroOidYieldsZero(): void
    {
        self::assertSame(0, self::payloadFor(['id' => 'news', 'oid' => '0'])->objectId);
    }

    public function testIdIsReadAsString(): void
    {
        self::assertSame('news', self::payloadFor(['id' => 'news'])->id);
    }

    public function testAbsentIdYieldsNull(): void
    {
        self::assertNull(self::payloadFor([])->id);
    }
}
