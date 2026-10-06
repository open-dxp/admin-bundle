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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Tree;

use OpenDxp\Bundle\AdminBundle\Tests\Value\ElementKind;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Test\Factory\UserRoleFactory;
use OpenDxp\TestFoundation\Browser;

/**
 * The tree the workspace tests look into, with one element kind throughout:
 *
 *     /permissionfoo/bars/hugo
 *     /permissionfoo/bars/userfolder/usertestobject
 *     /permissionfoo/bars/groupfolder/grouptestobject
 *     /permissionbar/foo/hiddenobject
 *
 * @return array<string, ElementInterface> every element by its key
 */
function elementTree(ElementKind $kind): array
{
    $tree['permissionfoo'] = $kind->folder('permissionfoo')
        ->create();
    $tree['permissionbar'] = $kind->folder('permissionbar')
        ->create();

    $tree['foo'] = $kind->folder('foo')
        ->withParent($tree['permissionbar'])
        ->create();
    $tree['bars'] = $kind->folder('bars')
        ->withParent($tree['permissionfoo'])
        ->create();
    $tree['userfolder'] = $kind->folder('userfolder')
        ->withParent($tree['bars'])
        ->create();
    $tree['groupfolder'] = $kind->folder('groupfolder')
        ->withParent($tree['bars'])
        ->create();

    $tree['hiddenobject'] = $kind->leaf('hiddenobject')
        ->withParent($tree['foo'])
        ->create();
    $tree['hugo'] = $kind->leaf('hugo')
        ->withParent($tree['bars'])
        ->create();
    $tree['usertestobject'] = $kind->leaf('usertestobject')
        ->withParent($tree['userfolder'])
        ->create();
    $tree['grouptestobject'] = $kind->leaf('grouptestobject')
        ->withParent($tree['groupfolder'])
        ->create();

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
 * - user6 opens the object in the user folder but has no permission for the element kind at all.
 *
 * @param array<string, ElementInterface> $tree
 *
 * @return array<string, User> every user by its name
 */
function workspaceUsers(ElementKind $kind, array $tree): array
{
    $openingRole = $kind->withWorkspaces(
        UserRoleFactory::new(),
        open: [$tree['groupfolder']],
        closed: [],
    );
    $closingRole = $kind->withWorkspaces(
        UserRoleFactory::new(),
        open: [],
        closed: [$tree['groupfolder']],
    );

    $member = UserFactory::new()
        ->withPermissions($kind->permission)
        ->withRoles($openingRole->create(), $closingRole->create());

    $user1 = $kind->withWorkspaces(
        $member,
        open: [$tree['permissionfoo'], $tree['permissionbar'], $tree['userfolder']],
        closed: [$tree['foo'], $tree['bars']],
    );
    $user2 = $kind->withWorkspaces(
        $member,
        open: [$tree['permissionfoo'], $tree['permissionbar'], $tree['userfolder']],
        closed: [$tree['foo'], $tree['bars'], $tree['groupfolder']],
    );
    $user3 = $kind->withWorkspaces(
        UserFactory::new()
            ->withPermissions($kind->permission),
        open: [$tree['usertestobject']],
        closed: [],
    );
    $user5 = $kind->withWorkspaces(
        UserFactory::new()
            ->withPermissions('assets', $kind->permission),
        open: [$tree['usertestobject']],
        closed: [],
    );
    $user6 = $kind->withWorkspaces(
        UserFactory::new(),
        open: [$tree['usertestobject']],
        closed: [],
    );

    return [
        'admin' => UserFactory::new()
            ->admin()
            ->create(),
        'user1' => $user1->create(),
        'user2' => $user2->create(),
        'user3' => $user3->create(),
        'user4' => $member->create(),
        'user5' => $user5->create(),
        'user6' => $user6->create(),
    ];
}

/**
 * Asks the admin tree for the children of the element, as the user's browser would.
 *
 * @return list<string> the paths of the children the user sees
 */
function treeChildren(ElementKind $kind, ElementInterface $parent, User $user): array
{
    $content = Browser::actingAs($user)
        ->visit(sprintf(
            '/admin/%s/tree-get-children-by-id-paginated?node=%d&limit=100&view=0',
            $kind->name,
            $parent->getId(),
        ))
        ->content();

    return array_column(json_decode($content, true, flags: JSON_THROW_ON_ERROR)['nodes'], 'path');
}

/**
 * @param array<string, ElementInterface> $tree
 * @param list<string> $keys
 *
 * @return list<string>
 */
function pathsOf(array $tree, array $keys): array
{
    return array_map(static fn (string $key): string => $tree[$key]->getRealFullPath(), $keys);
}

it('lists the children a user may see', function (ElementKind $kind, string $parent, string $user, array $children) {
    $tree = elementTree($kind);
    $users = workspaceUsers($kind, $tree);

    $listed = treeChildren($kind, $tree[$parent], $users[$user]);

    expect($listed)->toEqualCanonicalizing(pathsOf($tree, $children));
})->with([
    'assets' => fn () => ElementKind::asset(),
    'documents' => fn () => ElementKind::document(),
    'objects' => fn () => ElementKind::object(),
])->with([
    'permissionfoo for the admin' => ['permissionfoo', 'admin', ['bars']],
    'permissionfoo for user1' => ['permissionfoo', 'user1', ['bars']],
    'permissionfoo for user2' => ['permissionfoo', 'user2', ['bars']],
    'bars for the admin' => ['bars', 'admin', ['hugo', 'userfolder', 'groupfolder']],
    'bars for user1' => ['bars', 'user1', ['userfolder', 'groupfolder']],
    'bars for user2' => ['bars', 'user2', ['userfolder']],
    'userfolder for the admin' => ['userfolder', 'admin', ['usertestobject']],
    'userfolder for user1' => ['userfolder', 'user1', ['usertestobject']],
    'userfolder for user2' => ['userfolder', 'user2', ['usertestobject']],
    'groupfolder for the admin' => ['groupfolder', 'admin', ['grouptestobject']],
    'groupfolder for user1' => ['groupfolder', 'user1', ['grouptestobject']],
    'groupfolder for user2' => ['groupfolder', 'user2', []],
    'permissionbar for the admin' => ['permissionbar', 'admin', ['foo']],
    'permissionbar for user1' => ['permissionbar', 'user1', []],
    'permissionbar for user2' => ['permissionbar', 'user2', []],
    'foo for the admin' => ['foo', 'admin', ['hiddenobject']],
    'foo for user1' => ['foo', 'user1', []],
    'foo for user2' => ['foo', 'user2', []],
]);

it('lists the objects a user may see through their own workspace or a role', function (
    string $parent,
    string $user,
    array $children,
) {
    $kind = ElementKind::object();
    $tree = elementTree($kind);
    $users = workspaceUsers($kind, $tree);

    $listed = treeChildren($kind, $tree[$parent], $users[$user]);

    expect($listed)->toEqualCanonicalizing(pathsOf($tree, $children));
})->with([
    'permissionfoo for user3' => ['permissionfoo', 'user3', ['bars']],
    'permissionfoo for user4' => ['permissionfoo', 'user4', ['bars']],
    'permissionfoo for user5' => ['permissionfoo', 'user5', ['bars']],
    'bars for user3' => ['bars', 'user3', ['userfolder']],
    'bars for user4' => ['bars', 'user4', ['groupfolder']],
    'bars for user5' => ['bars', 'user5', ['userfolder']],
    'userfolder for user3' => ['userfolder', 'user3', ['usertestobject']],
    'userfolder for user4' => ['userfolder', 'user4', []],
    'userfolder for user5' => ['userfolder', 'user5', ['usertestobject']],
    'groupfolder for user3' => ['groupfolder', 'user3', []],
    'groupfolder for user4' => ['groupfolder', 'user4', ['grouptestobject']],
    'groupfolder for user5' => ['groupfolder', 'user5', []],
    'permissionbar for user3' => ['permissionbar', 'user3', []],
    'permissionbar for user4' => ['permissionbar', 'user4', []],
    'permissionbar for user5' => ['permissionbar', 'user5', []],
    'foo for user3' => ['foo', 'user3', []],
    'foo for user4' => ['foo', 'user4', []],
    'foo for user5' => ['foo', 'user5', []],
]);

it('refuses the object tree to a user without the objects permission, whatever their workspaces open', function (
    string $parent,
) {
    $kind = ElementKind::object();
    $tree = elementTree($kind);
    $user = workspaceUsers($kind, $tree)['user6'];

    Browser::actingAs($user)
        ->visit(sprintf('/admin/object/tree-get-children-by-id-paginated?node=%d&limit=100', $tree[$parent]->getId()))
        ->assertStatus(403);
})->with([
    'bars' => ['bars'],
    'userfolder' => ['userfolder'],
    'groupfolder' => ['groupfolder'],
    'permissionbar' => ['permissionbar'],
    'foo' => ['foo'],
]);
