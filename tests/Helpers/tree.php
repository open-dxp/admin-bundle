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

use OpenDxp\Bundle\AdminBundle\Tests\Factory\InheritanceFactory;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User;
use OpenDxp\Model\User\Workspace;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Test\Factory\UserRoleFactory;
use OpenDxp\TestFoundation\Browser;

/**
 * The tree the workspace tests look into, with one element type throughout:
 *
 *     /permissionfoo/bars/hugo
 *     /permissionfoo/bars/userfolder/usertestobject
 *     /permissionfoo/bars/groupfolder/grouptestobject
 *     /permissionbar/foo/hiddenobject
 *
 * @return array<string, ElementInterface> every element by its key
 */
function elementTree(string $type): array
{
    $folder = static fn (string $key, ?ElementInterface $parent = null): ElementInterface => (match ($type) {
        'asset' => AssetFolderFactory::new(),
        'document' => DocumentFolderFactory::new(),
        'object' => DataObjectFolderFactory::new(),
    })->with(['key' => $key, 'parentId' => $parent?->getId() ?? 1])->create();

    $leaf = static fn (string $key, ElementInterface $parent): ElementInterface => (match ($type) {
        'asset' => AssetImageFactory::new()->with(['filename' => $key . '.jpg']),
        'document' => DocumentPageFactory::new()->with(['key' => $key]),
        'object' => InheritanceFactory::new()->with(['key' => $key]),
    })->withParent($parent)->create();

    $tree['permissionfoo'] = $folder('permissionfoo');
    $tree['permissionbar'] = $folder('permissionbar');
    $tree['foo'] = $folder('foo', $tree['permissionbar']);
    $tree['bars'] = $folder('bars', $tree['permissionfoo']);
    $tree['userfolder'] = $folder('userfolder', $tree['bars']);
    $tree['groupfolder'] = $folder('groupfolder', $tree['bars']);

    $tree['hiddenobject'] = $leaf('hiddenobject', $tree['foo']);
    $tree['hugo'] = $leaf('hugo', $tree['bars']);
    $tree['usertestobject'] = $leaf('usertestobject', $tree['userfolder']);
    $tree['grouptestobject'] = $leaf('grouptestobject', $tree['groupfolder']);

    return $tree;
}

/**
 * The users the workspace tests look with. Both roles name the group folder: one opens it, the other closes it.
 *
 * - user1 and user2 open both top folders and close foo and bars. user1 opens the user folder, user2 opens the user
 *   folder and closes the group folder. Both carry both roles.
 * - user3 opens only the object in the user folder and carries no role.
 * - user4 has no workspace of its own and carries both roles.
 * - user5 opens the object in the user folder and may see assets as well as objects.
 * - user6 opens the object in the user folder but has no permission for the element type at all.
 *
 * @param array<string, ElementInterface> $tree
 *
 * @return array<string, User> every user by its name
 */
function workspaceUsers(string $type, array $tree): array
{
    $permission = ['asset' => 'assets', 'document' => 'documents', 'object' => 'objects'][$type];
    $workspaces = 'workspaces' . ucfirst($type);

    $open = static fn (string $key): Workspace\AbstractWorkspace => workspace($type, $tree[$key], true);
    $closed = static fn (string $key): Workspace\AbstractWorkspace => workspace($type, $tree[$key], false);

    $roles = [
        UserRoleFactory::createOne([$workspaces => [$open('groupfolder')]])->getId(),
        UserRoleFactory::createOne([$workspaces => [$closed('groupfolder')]])->getId(),
    ];

    $topFolders = [$open('permissionfoo'), $open('permissionbar'), $closed('foo'), $closed('bars')];

    return [
        'admin' => UserFactory::new()->admin()->create(),
        'user1' => UserFactory::createOne([
            'permissions' => [$permission],
            'roles' => $roles,
            $workspaces => [...$topFolders, $open('userfolder')],
        ]),
        'user2' => UserFactory::createOne([
            'permissions' => [$permission],
            'roles' => $roles,
            $workspaces => [...$topFolders, $open('userfolder'), $closed('groupfolder')],
        ]),
        'user3' => UserFactory::createOne(['permissions' => [$permission], $workspaces => [$open('usertestobject')]]),
        'user4' => UserFactory::createOne(['permissions' => [$permission], 'roles' => $roles]),
        'user5' => UserFactory::createOne(['permissions' => ['assets', $permission], $workspaces => [$open('usertestobject')]]),
        'user6' => UserFactory::createOne(['permissions' => [], $workspaces => [$open('usertestobject')]]),
    ];
}

function workspace(string $type, ElementInterface $element, bool $open): Workspace\AbstractWorkspace
{
    $workspace = match ($type) {
        'asset' => new Workspace\Asset(),
        'document' => new Workspace\Document(),
        'object' => new Workspace\DataObject(),
    };

    return $workspace->setValues([
        'cId' => $element->getId(),
        'cPath' => $element->getRealFullPath(),
        'list' => $open,
        'view' => $open,
    ]);
}

/**
 * Asks the admin tree for the children of the element, as the user's browser would.
 *
 * @return list<string> the paths of the children the user sees
 */
function treeChildren(string $type, ElementInterface $parent, User $user): array
{
    $response = json_decode(
        Browser::actingAs($user)
            ->visit(sprintf('/admin/%s/tree-get-children-by-id-paginated?node=%d&limit=100&view=0', $type, $parent->getId()))
            ->assertSuccessful()
            ->content(),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($response['nodes'])->toHaveCount($response['total']);

    return array_column($response['nodes'], 'path');
}
