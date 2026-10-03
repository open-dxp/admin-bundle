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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\LoginLink;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\Login\LostPasswordEvent;
use OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword\LostPasswordHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword\LostPasswordPayload;
use OpenDxp\Test\Factory\UserFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

beforeEach(function () {
    $this->user = UserFactory::createOne(['email' => 'login-link@example.test']);

    $this->lostPasswordLinkFrom = function (string $host): string {
        $link = null;
        $events = new EventDispatcher();
        $events->addListener(AdminEvents::LOGIN_LOSTPASSWORD, static function (LostPasswordEvent $event) use (&$link): void {
            $link = $event->getLoginUrl();
            $event->setSendMail(false);
        });

        $handler = new LostPasswordHandler(
            resetPasswordLimiter: new RateLimiterFactory(['id' => 'login_link_test', 'policy' => 'no_limit'], new InMemoryStorage()),
            router: $this->router(),
            eventDispatcher: $events,
            hostResolver: $this->hostResolver(),
            requestStack: $this->requestStackFor($host),
        );

        $result = $handler(new LostPasswordPayload(username: $this->user->getName(), clientIp: '127.0.0.1', isPost: true));

        expect($result->error)->toBeNull()->and($link)->toBeString();

        return $link;
    };
});

it('links to the general host when the request comes from a host no site knows', function () {
    expect(self::hostOf(($this->lostPasswordLinkFrom)('attacker.example')))->toBe(self::GENERAL_HOST);
});

it('links to the host of the request when it belongs to a site', function () {
    $this->site($this->domain('site'));

    expect(self::hostOf(($this->lostPasswordLinkFrom)($this->domain('site'))))->toBe($this->domain('site'));
});

it('leaves the host of the router as it found it', function () {
    ($this->lostPasswordLinkFrom)('attacker.example');

    expect($this->router()->getContext()->getHost())->toBe('attacker.example');
});
