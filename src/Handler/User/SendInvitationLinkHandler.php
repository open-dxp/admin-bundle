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

use Exception;
use OpenDxp\Bundle\AdminBundle\Service\CustomLoginUrlGenerator;
use OpenDxp\Model\User;
use OpenDxp\Tool;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SendInvitationLinkHandler
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CustomLoginUrlGenerator $loginUrlGenerator,
        private readonly RouterInterface $router,
    ) {}

    public function __invoke(string $username, string $domain): SendInvitationLinkResult
    {
        $success = false;
        $message = '';

        if (!$username) {
            return new SendInvitationLinkResult(success: false, message: $message);
        }

        $user = User::getByName($username);
        if (!$user instanceof User) {
            return new SendInvitationLinkResult(success: false, message: 'User unknown <br />');
        }

        if (!$user->getActive()) {
            $message .= 'User is not active <br />';
        }

        if (!$user->getEmail()) {
            $message .= 'User has no email address <br />';
        }

        if (empty($message)) {
            if (!$domain) {
                return new SendInvitationLinkResult(success: false, message: 'No main domain set in system settings, unable to generate login invitation link');
            }

            if (!$user->getPassword()) {
                $user->setPassword(bin2hex(random_bytes(16)));
                $user->save();
            }

            $token = Tool\Authentication::generateTokenByUser($user);

            $context = $this->router->getContext();
            $context->setHost($domain);

            $loginUrl = $this->loginUrlGenerator->generate(['token' => $token, 'reset' => true]);

            try {
                $mail = Tool::getMail([$user->getEmail()], 'OpenDXP login invitation for ' . Tool::getHostname());
                $mail->setIgnoreDebugMode(true);
                $mail->text("Login to OpenDXP and change your password using the following link. This temporary login link will expire in  24 hours: \r\n\r\n" . $loginUrl);
                $mail->send();

                $success = true;
                $message = sprintf($this->translator->trans('invitation_link_sent', [], 'admin_ext'), $user->getEmail());
            } catch (\Symfony\Component\HttpKernel\Exception\BadRequestHttpException $e) {
                $message .= $e->getMessage() . ' <br />';
            } catch (Exception) {
                $message .= 'could not send email';
            }
        }

        return new SendInvitationLinkResult(success: $success, message: $message);
    }
}
