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

use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Generator\CustomLoginUrlGenerator;
use OpenDxp\Bundle\AdminBundle\Handler\User\SendInvitationLink\SendInvitationLinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\SendInvitationLink\SendInvitationLinkPayload;
use OpenDxp\Event\MailEvents;
use OpenDxp\Event\Model\MailEvent;
use OpenDxp\Mail;
use OpenDxp\Test\Factory\UserFactory;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Translation\IdentityTranslator;

beforeEach(function () {
    $this->user = UserFactory::createOne(['email' => 'login-link@example.test']);
    $this->sent = new \ArrayObject();

    $sent = $this->sent;
    OpenDxp::getEventDispatcher()->addListener(MailEvents::PRE_SEND, static function (MailEvent $event) use ($sent): void {
        $event->setArgument('mailer', new class($sent) implements MailerInterface {
            public function __construct(private readonly \ArrayObject $sent)
            {
            }

            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                $this->sent->append($message);
            }
        });
    });

    $this->invitationFrom = function (string $host): Mail {
        $handler = new SendInvitationLinkHandler(
            translator: new IdentityTranslator(),
            loginUrlGenerator: new CustomLoginUrlGenerator($this->router(), 'login_link_test_missing_route'),
            router: $this->router(),
            hostResolver: $this->hostResolver(),
            requestStack: $this->requestStackFor($host),
        );

        $handler(new SendInvitationLinkPayload($this->user->getName()));

        expect($this->sent)->toHaveCount(1)->and($this->sent[0])->toBeInstanceOf(Mail::class);

        return $this->sent[0];
    };

    $this->linkIn = function (Mail $mail): string {
        expect(preg_match('#https?://\S+#', (string) $mail->getTextBody(), $matches))->toBe(1);

        return $matches[0];
    };
});

it('links to the general host when the request comes from a host no site knows', function () {
    $mail = ($this->invitationFrom)('attacker.example');

    expect(self::hostOf(($this->linkIn)($mail)))->toBe(self::GENERAL_HOST);
});

it('links to the host of the request when it belongs to a site', function () {
    $this->site($this->domain('site'));
    $mail = ($this->invitationFrom)($this->domain('site'));

    expect(self::hostOf(($this->linkIn)($mail)))->toBe($this->domain('site'));
});

it('names the same host in the subject as in the link', function () {
    expect((string) ($this->invitationFrom)('attacker.example')->getSubject())->toContain(self::GENERAL_HOST);
});

it('leaves the host of the router as it found it', function () {
    ($this->invitationFrom)('attacker.example');

    expect($this->router()->getContext()->getHost())->toBe('attacker.example');
});
