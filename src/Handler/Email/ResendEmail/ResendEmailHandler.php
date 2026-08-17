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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ResendEmail;

use Exception;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\Email\UnusableRecipientDetector;
use OpenDxp\Helper\Mail as MailHelper;
use OpenDxp\Logger;
use OpenDxp\Mail;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Tool;
use ReflectionClass;
use Symfony\Component\Mime\Address;

final class ResendEmailHandler
{
    public function __construct(private readonly UnusableRecipientDetector $unusableRecipientDetector)
    {
    }

    public function __invoke(ResendEmailPayload $payload): void
    {
        $emailLog = Tool\Email\Log::getById($payload->id);
        if (!$emailLog instanceof Tool\Email\Log) {
            throw new AdminOperationFailedException('Email log with ID ' . $payload->id . ' not found.');
        }

        $mail = new Mail();
        $mail->preventDebugInformationAppending();
        $mail->setIgnoreDebugMode(true);

        $isForward = !empty($payload->fieldOverrides['to']);

        if ($isForward) {
            $emailLog->setTo(null);
            $emailLog->setCc(null);
            $emailLog->setBcc(null);
        } else {
            $mail->disableLogging();
        }

        $skipDocumentRecipients = $isForward
            || $payload->useOriginalRecipients
            || $this->unusableRecipientDetector->hasUnusableRecipients($emailLog->getDocumentId());

        if ($skipDocumentRecipients) {
            $mail->clearRecipients();
        }

        if ($html = $emailLog->getHtmlLog()) {
            $mail->html($html);
        }

        if ($text = $emailLog->getTextLog()) {
            $mail->text($text);
        }

        // an email has a single sender, same as Mail::setDocumentSettings() picks it
        $sender = $this->resolveAddresses($payload, $emailLog, 'From')[0] ?? null;

        foreach (['To', 'Cc', 'Bcc', 'ReplyTo'] as $field) {
            $addresses = $this->resolveAddresses($payload, $emailLog, $field);

            if ($addresses) {
                $mail->{'add' . $field}(...$addresses);
            }
        }

        $mail->subject($emailLog->getSubject());

        if ($emailLog->getDocumentId()) {
            $mail->setDocument($emailLog->getDocumentId());
        }

        // Mail::setDocumentSettings() replaces the sender with the document's one
        // and clearRecipients() does not guard that block
        if ($sender instanceof Address && ($skipDocumentRecipients || $mail->getFrom() === [])) {
            $mail->from($sender);
        }

        try {
            $params = $emailLog->getParams();
        } catch (Exception) {
            Logger::warning('Could not decode JSON param string');
            $params = [];
        }

        foreach ($params as $entry) {
            $data = null;
            $hasChildren = isset($entry['children']) && is_array($entry['children']);

            if ($hasChildren) {
                $childData = [];
                foreach ($entry['children'] as $childParam) {
                    $childData[$childParam['key']] = $this->parseLoggingParamObject($childParam);
                }
                $data = $childData;
            } else {
                $data = $this->parseLoggingParamObject($entry);
            }

            $mail->setParam($entry['key'], $data);
        }

        $mail->send();
    }

    /**
     * @return list<Address>
     */
    private function resolveAddresses(ResendEmailPayload $payload, Tool\Email\Log $emailLog, string $field): array
    {
        $override = $payload->fieldOverrides[strtolower($field)] ?? null;
        $values = empty($override) ? $emailLog->{'get' . $field}() : $override;

        $addresses = [];
        foreach (MailHelper::parseEmailAddressField($values) as $value) {
            $addresses[] = new Address($value['email'], $value['name']);
        }

        return $addresses;
    }

    private function parseLoggingParamObject(array $params): mixed
    {
        if ($params['data']['type'] === 'object') {
            $class = '\\' . ltrim($params['data']['objectClass'], '\\');
            $reflection = new ReflectionClass($class);

            if (!empty($params['data']['objectId']) && $reflection->implementsInterface(ElementInterface::class)) {
                $obj = $class::getById($params['data']['objectId']);
                if (!is_null($obj)) {
                    return $obj;
                }
            }

            return null;
        }

        return $params['data']['value'];
    }
}
