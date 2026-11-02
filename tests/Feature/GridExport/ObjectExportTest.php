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

use InvalidArgumentException;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportHeader;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\Source\ObjectGridExportSource;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\TestProductFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Container;

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
    $this->folder = DataObjectFolderFactory::createOne();
});

it('titles the columns with the labels of the grid', function () {
    TestProductFactory::new()
        ->withParent($this->folder)
        ->create();
    $parameters = objectGridParameters($this->folder->getId(), ['sku' => 'SKU']);

    $file = exportThroughAdmin($this->admin, 'objects', parameters: $parameters);

    expect($file)
        ->getHeader()
        ->toBe(['SKU']);
});

it('titles the columns with the system keys when the header asks for them', function () {
    TestProductFactory::new()
        ->withParent($this->folder)
        ->create();
    $parameters = objectGridParameters($this->folder->getId(), ['sku' => 'SKU']);

    $file = exportThroughAdmin(
        $this->admin,
        'objects',
        parameters: $parameters,
        settings: ['header' => GridExportHeader::KEY->value],
    );

    expect($file)
        ->getHeader()
        ->toBe(['sku']);
});

it('exports the values of the objects', function () {
    TestProductFactory::new()
        ->withParent($this->folder)
        ->create(['sku' => 'Exported']);
    $parameters = objectGridParameters($this->folder->getId(), ['sku' => 'SKU']);

    $file = exportThroughAdmin($this->admin, 'objects', parameters: $parameters);

    expect($file)
        ->getColumn('SKU')
        ->toBe(['Exported']);
});

it('exports inherited values when the export enables inheritance', function (string $enabled, array $values) {
    $parent = TestProductFactory::new()
        ->withParent($this->folder)
        ->create(['sku' => 'Inherited']);
    TestProductFactory::new()
        ->withParent($parent)
        ->create();
    $parameters = objectGridParameters(
        $this->folder->getId(),
        [
            'key' => 'Key',
            'sku' => 'SKU',
        ],
        ['enableInheritance' => $enabled],
    );

    $file = exportThroughAdmin($this->admin, 'objects', parameters: $parameters);

    expect($file)
        ->getColumn('SKU')
        ->toBe($values);
})->with([
    'enabled' => ['true', ['Inherited', 'Inherited']],
    'disabled' => ['false', ['Inherited', '']],
]);

it('exports only the selected objects', function () {
    $selected = TestProductFactory::new()
        ->withParent($this->folder)
        ->create(['sku' => 'Selected']);
    TestProductFactory::new()
        ->withParent($this->folder)
        ->create();
    $parameters = objectGridParameters($this->folder->getId(), ['sku' => 'SKU']);

    $file = exportThroughAdmin($this->admin, 'objects', parameters: $parameters, selectedIds: [$selected->getId()]);

    expect($file)
        ->getColumn('SKU')
        ->toBe(['Selected']);
});

it('exports an operator column of the object grid', function () {
    TestProductFactory::new()
        ->withParent($this->folder)
        ->create(['sku' => 'Exported']);
    $exportId = startObjectGridExportWithAnyGetter($this->admin, $this->folder->getId(), 'Any getter', 'sku');

    $file = writeGridExportFile($this->admin, $exportId);

    expect($file)
        ->getColumn('Any getter')
        ->toBe(['Exported']);
});

it('refuses an operator column without its definition', function () {
    TestProductFactory::new()
        ->withParent($this->folder)
        ->create();
    $query = new GridExportQuery(
        userId: $this->admin->getId(),
        language: 'en',
        timezone: 'UTC',
        parameters: objectGridParameters($this->folder->getId(), ['#missing' => 'Missing']),
    );

    expect(fn () => Container::get(ObjectGridExportSource::class)->getColumns($query))->toThrow(
        InvalidArgumentException::class,
        'The object grid export has no definition of the operator column "#missing".',
    );
});
