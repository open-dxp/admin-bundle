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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\AddUser;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\User;

final class AddUserHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(AddUserPayload $payload): AddUserResult
    {
        try {
            $currentUserIsAdmin = $this->userContext->getAdminUser()?->isAdmin() ?? false;
            $className = User\Service::getClassNameForType($payload->type);
            $user = $className::create([
                'parentId' => $payload->parentId,
                'name' => $payload->name,
                'password' => '',
                'active' => $payload->active,
            ]);

            if ($payload->referenceId !== null) {
                $rObject = $className::getById($payload->referenceId);
                if ($rObject && ($payload->type === 'user' || $payload->type === 'role')) {
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
                    if ($payload->type === 'user') {
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
        } catch (\Exception $e) {
            throw new AdminOperationFailedException($e->getMessage());
        }

        return new AddUserResult(id: $user->getId());
    }
}
