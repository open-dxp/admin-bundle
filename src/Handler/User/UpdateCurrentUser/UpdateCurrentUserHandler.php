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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\UpdateCurrentUser;

use Exception;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\PasswordResetSessionGateway;
use OpenDxp\Model\User;
use OpenDxp\Tool;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UpdateCurrentUserHandler
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly AdminUserContextInterface $userContext,
        private readonly PasswordResetSessionGateway $passwordResetSession,
    ) {}

    /**
     * @throws Exception
     * @throws AdminOperationFailedException
     */
    public function __invoke(UpdateCurrentUserPayload $payload): void
    {
        $user = $this->userContext->getAdminUser();
        $values = $payload->values;
        unset($values['name'], $values['id'], $values['admin'], $values['permissions'], $values['roles'], $values['active']);

        if (!empty($values['new_password'])) {
            $oldPasswordCheck = false;

            if ($this->passwordResetSession->isPasswordReset()) {
                $oldPasswordCheck = true;
            } elseif (!empty($values['old_password'])) {
                $errors = $this->validator->validate($values['old_password'], [new UserPassword()]);

                if (count($errors) === 0) {
                    $oldPasswordCheck = true;
                }
            }

            if (strlen($values['new_password']) < 10) {
                throw new Exception('Passwords have to be at least 10 characters long');
            }

            if ($oldPasswordCheck && $values['new_password'] == $values['retype_password']) {
                if (Tool\Authentication::verifyPassword($user, $values['new_password'])) {
                    throw new Exception('The new password cannot be the same as the old one');
                }

                $values['password'] = Tool\Authentication::getPasswordHash($user->getName(), $values['new_password']);
            } else {
                if (!$oldPasswordCheck) {
                    throw new AdminOperationFailedException('incorrect_password');
                }

                throw new AdminOperationFailedException('password_cannot_be_changed');
            }
        }

        $user->setValues($values);

        if ($payload->keyBindingsJson !== null) {
            $keyBindings = json_decode($payload->keyBindingsJson, true);
            $tmpArray = [];
            foreach ($keyBindings as $item) {
                $tmpArray[] = json_decode($item, true);
            }
            $tmpArray = array_values(array_filter($tmpArray));
            $tmpArray = json_encode($tmpArray) ?: '';

            $user->setKeyBindings($tmpArray);
        }

        $user->save();
    }
}
