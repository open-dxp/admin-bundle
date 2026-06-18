<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\SendTestEmail;

use OpenDxp\Mail;
use OpenDxp\Model\Document;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mime\Address;

final class SendTestEmailHandler
{
    public function __invoke(SendTestEmailPayload $payload): void
    {
        $mail = new Mail();

        if ($payload->emailType === 'text') {
            $mail->text(strip_tags($payload->content ?? ''));
        } elseif ($payload->emailType === 'html') {
            $mail->html($payload->content ?? '');
        } elseif ($payload->emailType === 'document') {
            $doc = Document::getByPath($payload->documentPath ?? '');

            if (!$doc instanceof Document\Email) {
                throw new BadRequestHttpException('Email document not found!');
            }

            $mail->setDocument($doc);

            if ($payload->mailParameters) {
                foreach ($payload->mailParameters as $mailParam) {
                    if ($mailParam['key']) {
                        $mail->setParam($mailParam['key'], $mailParam['value']);
                    }
                }
            }
        }

        if ($payload->from) {
            $addressArray = \OpenDxp\Helper\Mail::parseEmailAddressField($payload->from);
            if ($addressArray) {
                [$cleanedFromAddress] = $addressArray;
                $mail->from(new Address($cleanedFromAddress['email'], $cleanedFromAddress['name']));
            }
        }

        $toAddresses = \OpenDxp\Helper\Mail::parseEmailAddressField($payload->to);
        foreach ($toAddresses as $cleanedToAddress) {
            $mail->addTo($cleanedToAddress['email'], $cleanedToAddress['name']);
        }

        $mail->subject($payload->subject);
        $mail->setIgnoreDebugMode(true);
        $mail->send();
    }
}
