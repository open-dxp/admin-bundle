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

namespace OpenDxp\Bundle\AdminBundle\Handler\User;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\User;

final class AddUserHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(
        string $type,
        int $parentId,
        string $name,
        bool $active,
        ?int $referenceId,
    ): AddUserResult {
        $currentUserIsAdmin = $this->userContext->getAdminUser()?->isAdmin() ?? false;
        $className = User\Service::getClassNameForType($type);
        $user = $className::create([
            'parentId' => $parentId,
            'name' => $name,
            'password' => '',
            'active' => $active,
        ]);

        if ($referenceId !== null) {
            $rObject = $className::getById($referenceId);
            if ($rObject && ($type === 'user' || $type === 'role')) {
                $user->setParentId($rObject->getParentId());
                if ($rObject->getClasses()) {
                    $user->setClasses(implode(',', $rObject->getClasses()));
                }
                if ($rObject->getDocTypes()) {
                    $user->setDocTypes(implode(',', $rObject->getDocTypes()));
                }
                $keys = ['asset', 'document', 'object'];
                foreach ($keys as $key) {
                    $getter = 'getWorkspaces' . ucfirst($key);
                    $setter = 'setWorkspaces' . ucfirst($key);
                    $workspaces = $rObject->$getter();
                    $clonedWorkspaces = [];
                    if (is_array($workspaces)) {
                        /** @var User\Workspace\AbstractWorkspace $workspace */
                        foreach ($workspaces as $workspace) {
                            $vars = $workspace->getObjectVars();
                            if ($key === 'object') {
                                $workspaceClass = \OpenDxp\Model\User\Workspace\DataObject::class;
                            } else {
                                $workspaceClass = '\\OpenDxp\\Model\\User\\Workspace\\' . ucfirst($key);
                            }
                            $newWorkspace = new $workspaceClass();
                            foreach ($vars as $varKey => $varValue) {
                                $newWorkspace->setObjectVar($varKey, $varValue);
                            }
                            $newWorkspace->setUserId($user->getId());
                            $clonedWorkspaces[] = $newWorkspace;
                        }
                    }

                    $user->$setter($clonedWorkspaces);
                }
                $user->setPerspectives($rObject->getPerspectives());
                $user->setPermissions($rObject->getPermissions());
                if ($type === 'user') {
                    $user->setAdmin(false);
                    if ($currentUserIsAdmin) {
                        $user->setAdmin($rObject->getAdmin());
                    }
                    $user->setActive($rObject->getActive());
                    $user->setRoles($rObject->getRoles());
                    $user->setWelcomeScreen($rObject->getWelcomescreen());
                    $user->setMemorizeTabs($rObject->getMemorizeTabs());
                    $user->setCloseWarning($rObject->getCloseWarning());
                }
                $user->setWebsiteTranslationLanguagesView($rObject->getWebsiteTranslationLanguagesView());
                $user->setWebsiteTranslationLanguagesEdit($rObject->getWebsiteTranslationLanguagesEdit());
                $user->save();
            }
        }

        return new AddUserResult(id: $user->getId());
    }
}
