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

namespace OpenDxp\Bundle\AdminBundle\Tests\TestCase;

use ArrayObject;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\Login\LostPasswordEvent;
use OpenDxp\Bundle\AdminBundle\Generator\CustomLoginUrlGenerator;
use OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword\LostPasswordHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword\LostPasswordPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetTokenLoginLink\GetTokenLoginLinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetTokenLoginLink\GetTokenLoginLinkPayload;
use OpenDxp\Bundle\AdminBundle\Handler\User\SendInvitationLink\SendInvitationLinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\SendInvitationLink\SendInvitationLinkPayload;
use OpenDxp\Bundle\AdminBundle\Security\TrustedLoginLinkHostResolver;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Event\MailEvents;
use OpenDxp\Event\Model\MailEvent;
use OpenDxp\Http\Request\Host\GeneralHostProviderInterface;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use OpenDxp\Mail;
use OpenDxp\Model\User;
use OpenDxp\Security\User\User as UserProxy;
use OpenDxp\TestFoundation\Container;
use OpenDxp\TestFoundation\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\IdentityTranslator;

class LoginLinkTestCase extends TestCase
{
    protected const string GENERAL_HOST = 'general.login-link-test.example';

    protected GeneralHostProviderInterface $generalHosts;

    private string $run;

    protected function setUp(): void
    {
        parent::setUp();

        $this->run = uniqid();
        $this->generalHosts = self::generalHostProvider(self::GENERAL_HOST);
    }

    /**
     * The provider remembers the context it was asked with, so a test can see whether it was asked at all.
     */
    protected static function generalHostProvider(string $host): GeneralHostProviderInterface
    {
        return new class($host) implements GeneralHostProviderInterface {
            /**
             * @var list<array<string, mixed>>
             */
            public array $contexts = [];

            public function __construct(private readonly string $host)
            {
            }

            public function provide(array $context = []): ?string
            {
                $this->contexts[] = $context;

                return $this->host;
            }
        };
    }

    /**
     * OpenDXP caches the site of a domain beyond the transaction of a test, so every test uses domains of its own.
     */
    protected function domain(string $label): string
    {
        return sprintf('%s.%s.login-link-test.example', $label, $this->run);
    }

    protected function hostResolver(): TrustedLoginLinkHostResolver
    {
        return new TrustedLoginLinkHostResolver(new GeneralHostResolver([$this->generalHosts]));
    }

    protected function requestFor(string $host): Request
    {
        return Request::create(sprintf('https://%s/admin/login/lostpassword', $host));
    }

    /**
     * Symfony's RouterListener takes the host of the request into the router context as it is, and so does this.
     */
    protected function requestStackFor(string $host): RequestStack
    {
        $request = $this->requestFor($host);
        Container::get(RouterInterface::class)->getContext()->fromRequest($request);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }

    protected function invitationFrom(string $host, User $user): Mail
    {
        $sent = new ArrayObject();
        OpenDxp::getEventDispatcher()->addListener(
            MailEvents::PRE_SEND,
            static function (MailEvent $event) use ($sent): void {
                $event->setArgument('mailer', new class($sent) implements MailerInterface {
                    /**
                     * @param ArrayObject<int, RawMessage> $sent
                     */
                    public function __construct(private readonly ArrayObject $sent)
                    {
                    }

                    public function send(RawMessage $message, ?Envelope $envelope = null): void
                    {
                        $this->sent->append($message);
                    }
                });
            },
        );

        $router = Container::get(RouterInterface::class);
        $handler = new SendInvitationLinkHandler(
            translator: new IdentityTranslator(),
            loginUrlGenerator: new CustomLoginUrlGenerator($router, 'login_link_test_missing_route'),
            router: $router,
            hostResolver: $this->hostResolver(),
            requestStack: $this->requestStackFor($host),
        );

        $handler(new SendInvitationLinkPayload($user->getName()));

        return $sent[0];
    }

    protected function lostPasswordLinkFrom(string $host, User $user): string
    {
        $link = null;
        $events = new EventDispatcher();
        $events->addListener(
            AdminEvents::LOGIN_LOSTPASSWORD,
            static function (LostPasswordEvent $event) use (&$link): void {
                $link = $event->getLoginUrl();
                $event->setSendMail(false);
            },
        );

        $handler = new LostPasswordHandler(
            resetPasswordLimiter: new RateLimiterFactory(
                [
                    'id' => 'login_link_test',
                    'policy' => 'no_limit',
                ],
                new InMemoryStorage(),
            ),
            router: Container::get(RouterInterface::class),
            eventDispatcher: $events,
            hostResolver: $this->hostResolver(),
            requestStack: $this->requestStackFor($host),
        );

        $handler(new LostPasswordPayload(
            username: $user->getName(),
            clientIp: '127.0.0.1',
            isPost: true,
        ));

        return $link;
    }

    protected function tokenLoginLinkFrom(string $host, User $user): string
    {
        $router = Container::get(RouterInterface::class);
        $handler = new GetTokenLoginLinkHandler(
            userContext: new class() implements AdminUserContextInterface {
                public function getAdminUser(): ?User
                {
                    return null;
                }

                public function getAdminUserProxy(): ?UserProxy
                {
                    return null;
                }
            },
            loginUrlGenerator: new CustomLoginUrlGenerator($router, 'login_link_test_missing_route'),
            translator: new IdentityTranslator(),
            hostResolver: $this->hostResolver(),
            requestStack: $this->requestStackFor($host),
            router: $router,
        );

        return $handler(new GetTokenLoginLinkPayload($user->getId()))->link;
    }

    protected static function hostOf(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? $host : null;
    }
}
