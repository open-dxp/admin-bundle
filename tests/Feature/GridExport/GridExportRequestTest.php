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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\GridExport;

use OpenDxp\Test\Factory\UserFactory;

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
});

it('refuses a source that does not exist', function () {
    requestGridExport($this->admin, 'unknown')->assertStatus(404);
});

it('refuses settings that the export does not know', function (array $settings) {
    requestGridExport($this->admin, 'mock', settings: $settings)->assertStatus(400);
})->with([
    'a format' => [['format' => 'pdf']],
    'a header' => [['header' => 'labels']],
    'a delimiter of two characters' => [['delimiter' => ';;']],
    'a timezone' => [['timezone' => 'Europe/Nowhere']],
]);

it('runs an export in the browser and refuses a job run while the option is off', function () {
    $answer = json_decode(requestGridExport($this->admin, 'mock')->content(), true, flags: JSON_THROW_ON_ERROR);

    expect($answer)->toHaveKey('canCreateJobRun', false);

    createGridExportJobRun($this->admin, $answer['id'])->assertStatus(409);
});

it('refuses a batch that the export does not have', function () {
    $id = startGridExport($this->admin, 'mock');

    writeGridExportBatch($this->admin, $id, 2)->assertStatus(400);
});

it('hides an export from every other user', function () {
    $id = startGridExport($this->admin, 'mock');
    $other = UserFactory::new()
        ->admin()
        ->create();

    writeGridExportBatch($other, $id, 0)->assertStatus(404);
});

it('refuses the download of an export that misses a batch', function () {
    $id = startGridExport($this->admin, 'mock');
    writeGridExportBatch($this->admin, $id, 0);

    downloadGridExport($this->admin, $id)->assertStatus(409);
});

it('downloads the file of an export once every batch is written', function () {
    $id = startGridExport($this->admin, 'mock');
    writeGridExportBatch($this->admin, $id, 0);
    writeGridExportBatch($this->admin, $id, 1);

    downloadGridExport($this->admin, $id)->assertSuccessful();
});

it('removes an export once it is downloaded', function () {
    $id = startGridExport($this->admin, 'mock');
    writeGridExportBatch($this->admin, $id, 0);
    writeGridExportBatch($this->admin, $id, 1);

    downloadGridExport($this->admin, $id);

    expect(findGridExport($id))->toBeNull();
});

it('removes an export that the user cancels', function () {
    $id = startGridExport($this->admin, 'mock');

    deleteGridExport($this->admin, $id);

    expect(findGridExport($id))->toBeNull();
});
