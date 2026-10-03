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

use OpenDxp\TestFoundation\Browser;

beforeEach(function () {
    $this->childrenOf = function (string $type, string $parent, string $user): array {
        $tree = elementTree($type);

        return [
            treeChildren($type, $tree[$parent], workspaceUsers($type, $tree)[$user]),
            static fn (array $keys): array => array_map(static fn (string $key): string => $tree[$key]->getRealFullPath(), $keys),
        ];
    };
});

it('lists the children a user may see', function (string $type, string $parent, string $user, array $children) {
    [$listed, $pathsOf] = ($this->childrenOf)($type, $parent, $user);

    expect($listed)->toEqualCanonicalizing($pathsOf($children));
})->with(['asset', 'document', 'object'])->with([
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

it('lists the object children a user may see through a workspace of their own or of a role', function (string $parent, string $user, array $children) {
    [$listed, $pathsOf] = ($this->childrenOf)('object', $parent, $user);

    expect($listed)->toEqualCanonicalizing($pathsOf($children));
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

it('refuses the object tree to a user without the objects permission, whatever their workspaces open', function (string $parent) {
    $tree = elementTree('object');
    $user = workspaceUsers('object', $tree)['user6'];

    Browser::actingAs($user)
        ->visit(sprintf('/admin/object/tree-get-children-by-id-paginated?node=%d&limit=100', $tree[$parent]->getId()))
        ->assertStatus(403);
})->with(['bars', 'userfolder', 'groupfolder', 'permissionbar', 'foo']);
