<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\User\GetRole;

use OpenDxp\Bundle\AdminBundle\Perspective\Config;
use OpenDxp\Model\Element;
use OpenDxp\Model\User;
use OpenDxp\Model\User\Workspace;
use OpenDxp\Tool;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetRoleHandler
{
    public function __invoke(GetRolePayload $payload): GetRoleResult
    {
        $role = User\Role::getById($payload->id);
        if (!$role) {
            throw new NotFoundHttpException('Role not found');
        }

        // workspaces
        $types = ['asset', 'document', 'object'];
        foreach ($types as $type) {
            /** @var Workspace\Document[]|Workspace\Asset[]|Workspace\DataObject[] $workspaces */
            $workspaces = $role->{'getWorkspaces' . ucfirst($type)}();
            foreach ($workspaces as $wKey => $workspace) {
                $el = Element\Service::getElementById($type, $workspace->getCid());
                if ($el) {
                    $workspaceVars = $workspace->getObjectVars();
                    $workspaceVars['path'] = $el->getRealFullPath();
                    $workspaces[$wKey] = $workspaceVars;
                }
            }
            $role->{'setWorkspaces' . ucfirst($type)}($workspaces);
        }

        $replaceFn = static fn ($value) => $value->getObjectVars();

        // get available permissions
        $availableUserPermissionsList = new User\Permission\Definition\Listing();
        $availableUserPermissionsList->setOrderKey('category');
        $availableUserPermissions = $availableUserPermissionsList->load();
        $availableUserPermissions = array_map($replaceFn, $availableUserPermissions);

        $availablePerspectives = Config::getAvailablePerspectives(null);

        return new GetRoleResult(
            role: $role->getObjectVars(),
            permissions: $role->generatePermissionList(),
            classes: $role->getClasses(),
            docTypes: $role->getDocTypes(),
            availablePermissions: $availableUserPermissions,
            availablePerspectives: $availablePerspectives,
            validLanguages: Tool::getValidLanguages(),
        );
    }
}
