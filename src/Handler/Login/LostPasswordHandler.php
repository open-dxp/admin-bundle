<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login;

use Exception;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\Login\LostPasswordEvent;
use OpenDxp\Logger;
use OpenDxp\Model\User;
use OpenDxp\Tool;
use OpenDxp\Tool\Authentication;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class LostPasswordHandler
{
    public function __construct(
        private readonly RateLimiterFactory $resetPasswordLimiter,
        private readonly RouterInterface $router,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(string $username, string $clientIp, string $domain): LostPasswordResult
    {
        if (!$username) {
            return new LostPasswordResult(error: 'user_unknown');
        }

        $user = User::getByName($username);
        if (!$user instanceof User) {
            return new LostPasswordResult(error: 'user_unknown');
        }

        $limiter = $this->resetPasswordLimiter->create($clientIp);
        if (false === $limiter->consume(1)->isAccepted()) {
            return new LostPasswordResult(error: 'user_reset_password_too_many_attempts');
        }

        if (!$user->isActive()) {
            return new LostPasswordResult(error: 'user_inactive');
        }

        if (!$user->getEmail()) {
            return new LostPasswordResult(error: 'user_no_email_address');
        }

        if (!$user->getPassword()) {
            return new LostPasswordResult(error: 'user_no_password');
        }

        $token = Authentication::generateTokenByUser($user);

        try {
            if (!$domain) {
                throw new Exception('No main domain set in system settings, unable to generate reset password link');
            }

            $context = $this->router->getContext();
            $context->setHost($domain);

            $loginUrl = $this->router->generate('opendxp_admin_login_check', [
                'token' => $token,
                'reset' => 'true',
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $event = new LostPasswordEvent($user, $loginUrl);
            $this->eventDispatcher->dispatch($event, AdminEvents::LOGIN_LOSTPASSWORD);

            if ($event->getSendMail()) {
                $mail = Tool::getMail([$user->getEmail()], 'OpenDXP lost password service');
                $mail->setIgnoreDebugMode(true);
                $mail->text("Login to OpenDXP and change your password using the following link. This temporary login link will expire in 24 hours: \r\n\r\n" . $loginUrl);
                $mail->send();
            }

            return new LostPasswordResult(
                error: null,
                eventResponse: $event->hasResponse() ? $event->getResponse() : null,
            );
        } catch (Exception $e) {
            Logger::error('Error sending password recovery email: ' . $e->getMessage());

            return new LostPasswordResult(error: 'lost_password_email_error');
        }
    }
}
