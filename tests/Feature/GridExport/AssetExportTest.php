<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\GridExport;

use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\UserFactory;

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
    $this->folder = AssetFolderFactory::createOne();
});

/**
 * @param array<string, string> $columns
 * @param array<string, string> $filters
 *
 * @return array<string, mixed>
 */
function assetGridParameters(int $folderId, array $columns, array $filters = []): array
{
    return [
        'folderId' => (string) $folderId,
        'language' => 'default',
        'columns' => array_map(
            static fn (string $key, string $label): array => ['key' => $key, 'label' => $label],
            array_keys($columns),
            $columns,
        ),
        ...$filters,
    ];
}

it('exports the columns of the grid without the preview', function () {
    $parameters = assetGridParameters($this->folder->getId(), [
        'preview~system' => 'Preview',
        'filename~system' => 'Filename',
        'size~system' => 'Size',
    ]);

    $file = exportThroughAdmin($this->admin, 'assets', parameters: $parameters);

    expect($file)
        ->getHeader()
        ->toBe(['Filename', 'Size']);
});

it('exports the system values of an asset', function () {
    $image = AssetImageFactory::new()
        ->withParent($this->folder)
        ->create(['filename' => 'export.jpg']);
    $parameters = assetGridParameters($this->folder->getId(), [
        'id~system' => 'ID',
        'filename~system' => 'Filename',
        'size~system' => 'Size',
    ]);

    $file = exportThroughAdmin($this->admin, 'assets', parameters: $parameters);

    expect($file->getRows()[1])->toBe([
        (string) $image->getId(),
        'export.jpg',
        (string) $image->getFileSize(),
    ]);
});

it('exports the metadata of an asset in the language of its column', function () {
    AssetImageFactory::new()
        ->withParent($this->folder)
        ->withMetadata('title', 'input', 'Titel', 'de')
        ->withMetadata('title', 'input', 'Title', 'en')
        ->create();
    $parameters = assetGridParameters($this->folder->getId(), ['title~de' => 'Title (de)']);

    $file = exportThroughAdmin($this->admin, 'assets', parameters: $parameters);

    expect($file)
        ->getColumn('Title (de)')
        ->toBe(['Titel']);
});

it('exports only the direct children of the folder when the grid shows only them', function () {
    AssetImageFactory::new()
        ->withParent($this->folder)
        ->create(['filename' => 'child.jpg']);
    $subfolder = AssetFolderFactory::new()
        ->withParent($this->folder)
        ->create();
    AssetImageFactory::new()
        ->withParent($subfolder)
        ->create(['filename' => 'grandchild.jpg']);
    $parameters = assetGridParameters(
        $this->folder->getId(),
        ['filename~system' => 'Filename'],
        ['only_direct_children' => 'true'],
    );

    $file = exportThroughAdmin($this->admin, 'assets', parameters: $parameters);

    expect($file)
        ->getColumn('Filename')
        ->toBe(['child.jpg']);
});

it('exports only the selected assets', function () {
    $selected = AssetImageFactory::new()
        ->withParent($this->folder)
        ->create(['filename' => 'selected.jpg']);
    AssetImageFactory::new()
        ->withParent($this->folder)
        ->create();
    $parameters = assetGridParameters($this->folder->getId(), ['filename~system' => 'Filename']);

    $file = exportThroughAdmin($this->admin, 'assets', parameters: $parameters, selectedIds: [$selected->getId()]);

    expect($file)
        ->getColumn('Filename')
        ->toBe(['selected.jpg']);
});
