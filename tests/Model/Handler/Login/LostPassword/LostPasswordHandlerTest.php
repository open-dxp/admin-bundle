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

namespace OpenDxp\Bundle\AdminBundle\Tests\Model\Handler\Login\LostPassword;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\Login\LostPasswordEvent;
use OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword\LostPasswordHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword\LostPasswordPayload;
use OpenDxp\Bundle\AdminBundle\Tests\Support\Test\LoginLinkHostTestCase;
use OpenDxp\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class LostPasswordHandlerTest extends LoginLinkHostTestCase
{
    private const string USERNAME = 'login-link-lost-password-user';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setName(self::USERNAME);
        $this->user->setEmail('login-link@example.test');
        $this->user->setPassword('not-empty');
        $this->user->setActive(true);
        $this->user->save();
    }

    protected function tearDown(): void
    {
        $this->user->delete();

        parent::tearDown();
    }

    public function testLinkUsesGeneralHostForUnknownRequestHost(): void
    {
        $link = $this->requestLostPasswordLinkFrom('attacker.example');

        self::assertSame(self::GENERAL_HOST, $this->hostOf($link));
    }

    public function testLinkUsesRequestHostForRegisteredSiteDomain(): void
    {
        $this->createSite($this->domain('site'));

        $link = $this->requestLostPasswordLinkFrom($this->domain('site'));

        self::assertSame($this->domain('site'), $this->hostOf($link));
    }

    public function testRouterHostIsRestoredAfterGeneratingTheLink(): void
    {
        $this->requestLostPasswordLinkFrom('attacker.example');

        $this->assertRouterHostIs('attacker.example');
    }

    private function requestLostPasswordLinkFrom(string $host): string
    {
        $loginUrl = null;
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(AdminEvents::LOGIN_LOSTPASSWORD, static function (LostPasswordEvent $event) use (&$loginUrl): void {
            $loginUrl = $event->getLoginUrl();
            $event->setSendMail(false);
        });

        $handler = new LostPasswordHandler(
            resetPasswordLimiter: new RateLimiterFactory(['id' => 'login_link_test', 'policy' => 'no_limit'], new InMemoryStorage()),
            router: $this->getRouter(),
            eventDispatcher: $eventDispatcher,
            hostResolver: $this->createHostResolver(),
            requestStack: $this->startRequestTo($host),
        );

        $result = ($handler)(new LostPasswordPayload(username: self::USERNAME, clientIp: '127.0.0.1', isPost: true));

        self::assertNull($result->error);
        self::assertIsString($loginUrl);

        return $loginUrl;
    }
}
