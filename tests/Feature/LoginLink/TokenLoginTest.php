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

use OpenDxp\Bundle\AdminBundle\Generator\CustomLoginUrlGenerator;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetTokenLoginLink\GetTokenLoginLinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\GetTokenLoginLink\GetTokenLoginLinkPayload;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\User;
use OpenDxp\Security\User\User as UserProxy;
use OpenDxp\Test\Factory\UserFactory;
use Symfony\Component\Translation\IdentityTranslator;

beforeEach(function () {
    $this->user = UserFactory::createOne();

    $this->tokenLoginLinkFrom = function (string $host): string {
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
            loginUrlGenerator: new CustomLoginUrlGenerator($this->router(), 'login_link_test_missing_route'),
            translator: new IdentityTranslator(),
            hostResolver: $this->hostResolver(),
            requestStack: $this->requestStackFor($host),
            router: $this->router(),
        );

        return $handler(new GetTokenLoginLinkPayload($this->user->getId()))->link;
    };
});

it('links to the general host when the request comes from a host no site knows', function () {
    expect(self::hostOf(($this->tokenLoginLinkFrom)('attacker.example')))->toBe(self::GENERAL_HOST);
});

it('links to the host of the request when it belongs to a site', function () {
    $this->site($this->domain('site'));

    expect(self::hostOf(($this->tokenLoginLinkFrom)($this->domain('site'))))->toBe($this->domain('site'));
});

it('leaves the host of the router as it found it', function () {
    ($this->tokenLoginLinkFrom)('attacker.example');

    expect($this->router()->getContext()->getHost())->toBe('attacker.example');
});
