<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\Login\LostPasswordEvent;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
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
        private readonly GeneralHostResolver $hostResolver,
    ) {}

    public function __invoke(LostPasswordPayload $payload): LostPasswordResult
    {
        if (!$payload->isPost) {
            return new LostPasswordResult(error: null);
        }

        $error = null;

        $user = User::getByName($payload->username);
        if (!$user instanceof User) {
            $error = 'user_unknown';
        }

        // consumed unconditionally, even for an unknown username, so enumeration attempts are rate-limited too
        $limiter = $this->resetPasswordLimiter->create($payload->clientIp);
        if (false === $limiter->consume(1)->isAccepted()) {
            $error = 'user_reset_password_too_many_attempts';
        }

        if (!$error) {
            if (!$user->isActive()) {
                $error = 'user_inactive';
            }
            if (!$user->getEmail()) {
                $error = 'user_no_email_address';
            }
            if (!$user->getPassword()) {
                $error = 'user_no_password';
            }
        }

        $eventResponse = null;

        if (!$error) {
            $token = Authentication::generateTokenByUser($user);

            try {
                $domain = $this->hostResolver->resolve($payload->resolverContext ?? []) ?? '';
                if (!$domain) {
                    throw new \Exception('No main domain set in system settings, unable to generate reset password link');
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

                if ($event->hasResponse()) {
                    $eventResponse = $event->getResponse();
                }
            } catch (\Exception $e) {
                Logger::error('Error sending password recovery email: ' . $e->getMessage());
                $error = 'lost_password_email_error';
            }
        }

        if ($error) {
            Logger::error('Lost password service: ' . $error);
            // to avoid timing based enumeration
            usleep(random_int(50, 200));
        }

        return new LostPasswordResult(error: $error, eventResponse: $eventResponse);
    }
}
