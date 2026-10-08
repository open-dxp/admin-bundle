<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Application\Mailer;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * Keeps every message instead of sending it.
 */
final class MockMailer implements MailerInterface
{
    /**
     * @var list<RawMessage>
     */
    public array $sent = [];

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        $this->sent[] = $message;
    }
}
