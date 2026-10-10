<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\GridExport;

use OpenDxp\Bundle\AdminBundle\GridExport\GridExportHeader;
use OpenDxp\Bundle\AdminBundle\Tests\Factory\InheritanceFactory;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\UserFactory;

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
    $this->folder = DataObjectFolderFactory::createOne();
});

/**
 * @param array<string, string> $columns
 * @param array<string, string> $settings
 *
 * @return array<string, mixed>
 */
function objectGridParameters(int $folderId, array $columns, array $settings = []): array
{
    return [
        'folderId' => (string) $folderId,
        'classId' => ClassDefinition::getByName('Inheritance')->getId(),
        'language' => 'en',
        'fields' => array_keys($columns),
        'columns' => array_map(
            static fn (string $key, string $label): array => ['key' => $key, 'label' => $label],
            array_keys($columns),
            $columns,
        ),
        ...$settings,
    ];
}

it('titles the columns with the labels of the grid', function () {
    InheritanceFactory::new()
        ->withParent($this->folder)
        ->create();
    $parameters = objectGridParameters($this->folder->getId(), ['normalinput' => 'Normal input']);

    $file = exportThroughAdmin($this->admin, 'objects', parameters: $parameters);

    expect($file)
        ->getHeader()
        ->toBe(['Normal input']);
});

it('titles the columns with the system keys when the header asks for them', function () {
    InheritanceFactory::new()
        ->withParent($this->folder)
        ->create();
    $parameters = objectGridParameters($this->folder->getId(), ['normalinput' => 'Normal input']);

    $file = exportThroughAdmin(
        $this->admin,
        'objects',
        parameters: $parameters,
        settings: ['header' => GridExportHeader::KEY->value],
    );

    expect($file)
        ->getHeader()
        ->toBe(['normalinput']);
});

it('exports the values of the objects', function () {
    InheritanceFactory::new()
        ->withParent($this->folder)
        ->create(['normalinput' => 'Exported']);
    $parameters = objectGridParameters($this->folder->getId(), ['normalinput' => 'Normal input']);

    $file = exportThroughAdmin($this->admin, 'objects', parameters: $parameters);

    expect($file)
        ->getColumn('Normal input')
        ->toBe(['Exported']);
});

it('exports inherited values when the export enables inheritance', function (string $enabled, array $values) {
    $parent = InheritanceFactory::new()
        ->withParent($this->folder)
        ->create(['normalinput' => 'Inherited']);
    InheritanceFactory::new()
        ->withParent($parent)
        ->create();
    $parameters = objectGridParameters(
        $this->folder->getId(),
        [
            'key' => 'Key',
            'normalinput' => 'Normal input',
        ],
        ['enableInheritance' => $enabled],
    );

    $file = exportThroughAdmin($this->admin, 'objects', parameters: $parameters);

    expect($file)
        ->getColumn('Normal input')
        ->toBe($values);
})->with([
    'enabled' => ['true', ['Inherited', 'Inherited']],
    'disabled' => ['false', ['Inherited', '']],
]);

it('exports only the selected objects', function () {
    $selected = InheritanceFactory::new()
        ->withParent($this->folder)
        ->create(['normalinput' => 'Selected']);
    InheritanceFactory::new()
        ->withParent($this->folder)
        ->create();
    $parameters = objectGridParameters($this->folder->getId(), ['normalinput' => 'Normal input']);

    $file = exportThroughAdmin($this->admin, 'objects', parameters: $parameters, selectedIds: [$selected->getId()]);

    expect($file)
        ->getColumn('Normal input')
        ->toBe(['Selected']);
});
