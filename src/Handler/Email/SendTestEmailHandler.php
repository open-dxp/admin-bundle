<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email;

use OpenDxp\Mail;
use OpenDxp\Model\Document;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mime\Address;

final class SendTestEmailHandler
{
    public function __invoke(
        string $emailType,
        ?string $content,
        ?string $documentPath,
        ?array $mailParameters,
        ?string $from,
        string $to,
        string $subject,
    ): void {
        $mail = new Mail();

        if ($emailType === 'text') {
            $mail->text(strip_tags($content ?? ''));
        } elseif ($emailType === 'html') {
            $mail->html($content ?? '');
        } elseif ($emailType === 'document') {
            $doc = Document::getByPath($documentPath ?? '');

            if (!$doc instanceof Document\Email) {
                throw new BadRequestHttpException('Email document not found!');
            }

            $mail->setDocument($doc);

            if ($mailParameters) {
                foreach ($mailParameters as $mailParam) {
                    if ($mailParam['key']) {
                        $mail->setParam($mailParam['key'], $mailParam['value']);
                    }
                }
            }
        }

        if ($from) {
            $addressArray = \OpenDxp\Helper\Mail::parseEmailAddressField($from);
            if ($addressArray) {
                [$cleanedFromAddress] = $addressArray;
                $mail->from(new Address($cleanedFromAddress['email'], $cleanedFromAddress['name']));
            }
        }

        $toAddresses = \OpenDxp\Helper\Mail::parseEmailAddressField($to);
        foreach ($toAddresses as $cleanedToAddress) {
            $mail->addTo($cleanedToAddress['email'], $cleanedToAddress['name']);
        }

        $mail->subject($subject);
        $mail->setIgnoreDebugMode(true);
        $mail->send();
    }
}
