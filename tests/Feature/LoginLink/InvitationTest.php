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

use OpenDxp\Mail;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\Routing\RouterInterface;

function linkIn(Mail $mail): string
{
    preg_match('#https?://\S+#', (string) $mail->getTextBody(), $matches);

    return $matches[0];
}

beforeEach(function () {
    $this->user = UserFactory::createOne(['email' => 'login-link@example.test']);
});

it('links to the general host when the request comes from a host no site knows', function () {
    $mail = $this->invitationFrom('attacker.example', $this->user);

    expect(self::hostOf(linkIn($mail)))->toBe(self::GENERAL_HOST);
});

it('links to the host of the request when it belongs to a site', function () {
    SiteFactory::createOne(['mainDomain' => $this->domain('site')]);

    $mail = $this->invitationFrom($this->domain('site'), $this->user);

    expect(self::hostOf(linkIn($mail)))->toBe($this->domain('site'));
});

it('names the host of the link in the subject', function () {
    $mail = $this->invitationFrom('attacker.example', $this->user);

    expect((string) $mail->getSubject())->toContain(self::hostOf(linkIn($mail)));
});

it('leaves the host of the router as it found it', function () {
    $this->invitationFrom('attacker.example', $this->user);

    expect(Container::get(RouterInterface::class)->getContext()->getHost())->toBe('attacker.example');
});
