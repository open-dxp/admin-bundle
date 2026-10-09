<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Unit\GridExport;

use LogicException;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportBatchStorage;
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
    $rows = [['id' => 1]];

    expect(fn () => (new GridExportBatchStorage())->writeBatch(exportOfIdAndName(), 0, $rows))
        ->toThrow(LogicException::class, 'The grid export source "people" returns a row without the column "name".');
});

it('refuses a row with a column the source does not declare', function () {
    $rows = [['id' => 1, 'name' => 'Ada', 'email' => 'ada@example.com']];

    expect(fn () => (new GridExportBatchStorage())->writeBatch(exportOfIdAndName(), 0, $rows))
        ->toThrow(
            LogicException::class,
            'The grid export source "people" returns a row with the undeclared columns "email".',
        );
});
