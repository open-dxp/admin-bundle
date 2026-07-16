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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\SendInvitationLink;

use Exception;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Generator\CustomLoginUrlGenerator;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use OpenDxp\Model\User;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SendInvitationLinkHandler
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CustomLoginUrlGenerator $loginUrlGenerator,
        private readonly RouterInterface $router,
        private readonly GeneralHostResolver $generalHostResolver,
        private readonly RequestStack $requestStack,
    ) {}

    public function __invoke(SendInvitationLinkPayload $payload): SendInvitationLinkResult
    {
        if (!$payload->username) {
            throw new AdminOperationFailedException();
        }

        $user = User::getByName($payload->username);
        if (!$user instanceof User) {
            throw new AdminOperationFailedException('User unknown <br />');
        }

        $message = '';

        if (!$user->getActive()) {
            $message .= 'User is not active <br />';
        }

        if (!$user->getEmail()) {
            $message .= 'User has no email address <br />';
        }

        if (!empty($message)) {
            throw new AdminOperationFailedException($message);
        }

        $domain = $this->generalHostResolver->resolve(['source' => $this->requestStack->getCurrentRequest()]) ?? '';

        if (!$domain) {
            throw new AdminOperationFailedException('No main domain set in system settings, unable to generate login invitation link');
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
        } catch (\Symfony\Component\HttpKernel\Exception\BadRequestHttpException $e) {
            throw new AdminOperationFailedException($e->getMessage() . ' <br />');
        } catch (Exception) {
            throw new AdminOperationFailedException('could not send email');
        }

        return new SendInvitationLinkResult(
            message: sprintf($this->translator->trans('invitation_link_sent', [], 'admin_ext'), $user->getEmail()),
        );
    }
}
