<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Grid;

use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Model\Asset;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Container;

/**
 * @return list<string>
 */
function assetGridPaths(User $user, Asset\Folder $folder): array
{
    $listing = Container::get(GridHelperService::class)->prepareAssetListingForGrid(
        ['folderId' => $folder->getId()],
        $user,
    );

    return array_map(
        static fn (Asset $asset): string => $asset->getRealFullPath(),
        $listing->load(),
    );
}

it('lists only the assets inside a workspace whose name contains an underscore', function () {
    $parent = AssetFolderFactory::createOne();
    $workspace = AssetFolderFactory::new()
        ->withParent($parent)
        ->create(['filename' => 'shop_de']);
    $neighbour = AssetFolderFactory::new()
        ->withParent($parent)
        ->create(['filename' => 'shopXde']);
    $inside = AssetImageFactory::new()
        ->withParent($workspace)
        ->create();
    AssetImageFactory::new()
        ->withParent($neighbour)
        ->create();
    $user = UserFactory::new()
        ->withPermissions('assets')
        ->withAssetWorkspace($workspace, 'list', 'view')
        ->create();

    $paths = assetGridPaths($user, $parent);

    expect($paths)->toBe([$inside->getRealFullPath()]);
});

it('hides a closed asset beside a closed folder that holds an open one', function () {
    $parent = AssetFolderFactory::createOne();
    $archive = AssetFolderFactory::new()
        ->withParent($parent)
        ->create(['filename' => 'archive']);
    $public = AssetFolderFactory::new()
        ->withParent($archive)
        ->create();
    $visible = AssetImageFactory::new()
        ->withParent($public)
        ->create();
    $hidden = AssetImageFactory::new()
        ->withParent($parent)
        ->create(['filename' => 'secret.jpg']);
    $user = UserFactory::new()
        ->withPermissions('assets')
        ->withAssetWorkspace($parent, 'list', 'view')
        ->withAssetWorkspace($archive)
        ->withAssetWorkspace($public, 'list', 'view')
        ->withAssetWorkspace($hidden)
        ->create();

    $paths = assetGridPaths($user, $parent);

    expect($paths)->toBe([$visible->getRealFullPath()]);
});
