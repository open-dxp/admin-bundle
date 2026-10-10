<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\GridExport;

use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportBatchStorage;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportCleanupTask;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSettings;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Container;

beforeEach(function () {
    $this->batchStorage = Container::get(GridExportBatchStorage::class);
});

it('deletes the batches of an export that no longer exists', function () {
    $this->batchStorage->writeBatch(
        new GridExport(
            id: 'expired',
            source: 'mock',
            query: new GridExportQuery(userId: 1, language: 'en', timezone: 'UTC'),
            settings: new GridExportSettings(),
            columns: [],
            total: 0,
            batchSize: 1,
        ),
        0,
        [],
    );

    Container::get(GridExportCleanupTask::class)->execute();

    expect($this->batchStorage->getExportIds())->not->toContain('expired');
});

it('keeps the batches of an export that is still running', function () {
    $admin = UserFactory::new()
        ->admin()
        ->create();
    $id = startGridExport($admin, 'mock');
    writeGridExportBatch($admin, $id, 0);

    Container::get(GridExportCleanupTask::class)->execute();

    expect($this->batchStorage->getExportIds())->toContain($id);
});
