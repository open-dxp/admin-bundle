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

namespace OpenDxp\Bundle\AdminBundle\Service\Email;

use OpenDxp\Helper\Mail as MailHelper;
use OpenDxp\Model\Document;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\ExceptionInterface as MimeException;

final class UnusableRecipientDetector
{
    public function hasUnusableRecipients(?int $documentId): bool
    {
        if ($documentId === null) {
            return false;
        }

        $document = Document\Email::getById($documentId);
        if (!$document instanceof Document\Email) {
            return false;
        }

        $addressFields = [
            $document->getFrom(),
            $document->getTo(),
            $document->getCc(),
            $document->getBcc(),
            $document->getReplyTo(),
        ];

        foreach ($addressFields as $addressField) {
            if (!$this->isUsableAddressField($addressField)) {
                return true;
            }
        }

        return false;
    }

    public function isUsableAddressField(?string $addressField): bool
    {
        foreach (MailHelper::parseEmailAddressField($addressField) as $entry) {
            try {
                new Address($entry['email'], $entry['name']);
            } catch (MimeException) {
                return false;
            }
        }

        return true;
    }
}
