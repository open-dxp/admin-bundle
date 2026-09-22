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

namespace OpenDxp\Bundle\AdminBundle\Tests\Model\Handler\User\SendInvitationLink;

use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Generator\CustomLoginUrlGenerator;
use OpenDxp\Bundle\AdminBundle\Handler\User\SendInvitationLink\SendInvitationLinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\User\SendInvitationLink\SendInvitationLinkPayload;
use OpenDxp\Bundle\AdminBundle\Tests\Support\Test\LoginLinkHostTestCase;
use OpenDxp\Event\MailEvents;
use OpenDxp\Event\Model\MailEvent;
use OpenDxp\Mail;
use OpenDxp\Model\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Translation\IdentityTranslator;

class SendInvitationLinkHandlerTest extends LoginLinkHostTestCase
{
    private const string USERNAME = 'login-link-invitation-user';

    private User $user;

    private ?Mail $sentMail = null;

    /**
     * @var callable
     */
    private $mailInterceptor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setName(self::USERNAME);
        $this->user->setEmail('login-link@example.test');
        $this->user->setPassword('not-empty');
        $this->user->setActive(true);
        $this->user->save();

        $this->mailInterceptor = function (MailEvent $event): void {
            $event->setArgument('mailer', new class ($this) implements MailerInterface {
                public function __construct(private readonly SendInvitationLinkHandlerTest $test)
                {
                }

                public function send(RawMessage $message, ?\Symfony\Component\Mailer\Envelope $envelope = null): void
                {
                    $this->test->recordMail($message);
                }
            });
        };
        OpenDxp::getEventDispatcher()->addListener(MailEvents::PRE_SEND, $this->mailInterceptor);
    }

    protected function tearDown(): void
    {
        OpenDxp::getEventDispatcher()->removeListener(MailEvents::PRE_SEND, $this->mailInterceptor);
        $this->user->delete();

        parent::tearDown();
    }

    /**
     * @internal only called by the mail interceptor of this test
     */
    public function recordMail(RawMessage $message): void
    {
        self::assertInstanceOf(Mail::class, $message);
        $this->sentMail = $message;
    }

    public function testInvitationLinkUsesGeneralHostForUnknownRequestHost(): void
    {
        $mail = $this->sendInvitationFrom('attacker.example');

        self::assertSame(self::GENERAL_HOST, $this->hostOf($this->linkIn($mail)));
    }

    public function testInvitationLinkUsesRequestHostForRegisteredSiteDomain(): void
    {
        $this->createSite($this->domain('site'));

        $mail = $this->sendInvitationFrom($this->domain('site'));

        self::assertSame($this->domain('site'), $this->hostOf($this->linkIn($mail)));
    }

    public function testSubjectNamesTheSameHostAsTheLink(): void
    {
        $mail = $this->sendInvitationFrom('attacker.example');

        self::assertStringContainsString(self::GENERAL_HOST, (string) $mail->getSubject());
    }

    public function testRouterHostIsRestoredAfterGeneratingTheLink(): void
    {
        $this->sendInvitationFrom('attacker.example');

        $this->assertRouterHostIs('attacker.example');
    }

    private function sendInvitationFrom(string $host): Mail
    {
        $handler = new SendInvitationLinkHandler(
            translator: new IdentityTranslator(),
            loginUrlGenerator: new CustomLoginUrlGenerator($this->getRouter(), 'login_link_test_missing_route'),
            router: $this->getRouter(),
            hostResolver: $this->createHostResolver(),
            requestStack: $this->startRequestTo($host),
        );

        ($handler)(new SendInvitationLinkPayload(self::USERNAME));

        self::assertInstanceOf(Mail::class, $this->sentMail);

        return $this->sentMail;
    }

    private function linkIn(Mail $mail): string
    {
        self::assertSame(1, preg_match('#https?://\S+#', (string) $mail->getTextBody(), $matches));

        return $matches[0];
    }
}
