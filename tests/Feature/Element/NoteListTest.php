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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Element;

use Doctrine\DBAL\Exception\InvalidFieldNameException;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteList\GetNoteListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\NoteListPayload;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\HttpFoundation\Request;

it('takes the property of a date filter as a column name', function () {
    $filter = json_encode([[
        'type' => 'date',
        'operator' => 'eq',
        'property' => 'date` IS NOT NULL OR `date',
        'value' => '2026-01-01',
    ]]);

    $payload = NoteListPayload::fromRequest(Request::create('/', 'POST', ['filter' => $filter]));

    expect(fn () => Container::get(GetNoteListHandler::class)($payload))->toThrow(InvalidFieldNameException::class);
});
