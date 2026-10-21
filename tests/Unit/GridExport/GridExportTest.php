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

namespace OpenDxp\Bundle\AdminBundle\Tests\Unit\GridExport;

use LogicException;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumn;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSettings;

function exportOfIdAndName(): GridExport
{
    return new GridExport(
        id: 'export',
        source: 'people',
        query: new GridExportQuery(userId: 1, language: 'en', timezone: 'UTC'),
        settings: new GridExportSettings(),
        columns: [
            new GridExportColumn('id', 'ID'),
            new GridExportColumn('name', 'Name'),
        ],
        total: 1,
        batchSize: 1,
    );
}

it('refuses a row without a value for a declared column', function () {
    expect(fn () => exportOfIdAndName()->getValues(['id' => 1]))
        ->toThrow(LogicException::class, 'The grid export source "people" returns a row without the column "name".');
});

it('refuses a row with a column the source does not declare', function () {
    $row = ['id' => 1, 'name' => 'Ada', 'email' => 'ada@example.com'];

    expect(fn () => exportOfIdAndName()->getValues($row))
        ->toThrow(
            LogicException::class,
            'The grid export source "people" returns a row with the undeclared columns "email".',
        );
});
