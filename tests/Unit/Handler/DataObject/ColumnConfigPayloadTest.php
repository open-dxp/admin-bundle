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

namespace OpenDxp\Bundle\AdminBundle\Tests\Unit\Handler\DataObject;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassDefinitionForColumnConfig\GetClassDefinitionForColumnConfigPayload;
use Symfony\Component\HttpFoundation\Request;

function columnConfigPayload(array $query): GetClassDefinitionForColumnConfigPayload
{
    return GetClassDefinitionForColumnConfigPayload::fromRequest(
        Request::create('/admin/class/get-class-definition-for-column-config', 'GET', $query),
    );
}

it('reads an object id that is not a number as no object', function (array $query) {
    expect(columnConfigPayload($query)->objectId)->toBe(0);
})->with([
    'no oid' => [['id' => 'news']],
    'an empty oid' => [['id' => 'news', 'oid' => '']],
    'the string undefined' => [['id' => 'news', 'oid' => 'undefined']],
    'the string null' => [['id' => 'news', 'oid' => 'null']],
    'zero' => [['id' => 'news', 'oid' => '0']],
]);

it('reads a numeric object id as a number', function () {
    expect(columnConfigPayload(['id' => 'news', 'oid' => '42'])->objectId)->toBe(42);
});

it('reads the class id as a string', function () {
    expect(columnConfigPayload(['id' => 'news'])->id)->toBe('news');
});

it('reads a missing class id as null', function () {
    expect(columnConfigPayload([])->id)->toBeNull();
});
