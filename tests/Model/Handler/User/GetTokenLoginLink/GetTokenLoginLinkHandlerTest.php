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

namespace OpenDxp\Bundle\AdminBundle\Tests\Model\Handler\User\GetTokenLoginLink;

use OpenDxp\Bundle\AdminBundle\Generator\CustomLoginUrlGenerator;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetTokenLoginLink\GetTokenLoginLinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetTokenLoginLink\GetTokenLoginLinkPayload;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Tests\Support\Test\LoginLinkHostTestCase;
use OpenDxp\Model\User;
use OpenDxp\Security\User\User as UserProxy;
use Symfony\Component\Translation\IdentityTranslator;

class GetTokenLoginLinkHandlerTest extends LoginLinkHostTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setName('login-link-token-user');
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
        $link = $this->getLinkForRequestTo('attacker.example');

        self::assertSame(self::GENERAL_HOST, $this->hostOf($link));
    }

    public function testLinkUsesRequestHostForRegisteredSiteDomain(): void
    {
        $this->createSite($this->domain('site'));

        $link = $this->getLinkForRequestTo($this->domain('site'));

        self::assertSame($this->domain('site'), $this->hostOf($link));
    }

    public function testRouterHostIsRestoredAfterGeneratingTheLink(): void
    {
        $this->getLinkForRequestTo('attacker.example');

        $this->assertRouterHostIs('attacker.example');
    }

    private function getLinkForRequestTo(string $host): string
    {
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
            loginUrlGenerator: new CustomLoginUrlGenerator($this->getRouter(), 'login_link_test_missing_route'),
            translator: new IdentityTranslator(),
            hostResolver: $this->createHostResolver(),
            requestStack: $this->startRequestTo($host),
            router: $this->getRouter(),
        );

        return ($handler)(new GetTokenLoginLinkPayload($this->user->getId()))->link;
    }
}
