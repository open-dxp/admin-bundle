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

use OpenDxp\Logger;
use OpenDxp\Mail;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Tool;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\Address;

final class ResendEmailHandler
{
    /**
     * @param array<string, string|null> $fieldOverrides Keys: 'from', 'to', 'cc', 'bcc', 'replyto'
     */
    public function __invoke(int $id, array $fieldOverrides): void
    {
        $emailLog = Tool\Email\Log::getById($id);
        if (!$emailLog instanceof Tool\Email\Log) {
            throw new NotFoundHttpException('Email log with ID ' . $id . ' not found.');
        }

        $mail = new Mail();
        $mail->preventDebugInformationAppending();
        $mail->setIgnoreDebugMode(true);

        if (!empty($fieldOverrides['to'])) {
            $emailLog->setTo(null);
            $emailLog->setCc(null);
            $emailLog->setBcc(null);
        } else {
            $mail->disableLogging();
        }

        if ($html = $emailLog->getHtmlLog()) {
            $mail->html($html);
        }

        if ($text = $emailLog->getTextLog()) {
            $mail->text($text);
        }

        foreach (['From', 'To', 'Cc', 'Bcc', 'ReplyTo'] as $field) {
            $overrideKey = strtolower($field);
            if (!empty($fieldOverrides[$overrideKey])) {
                $values = $fieldOverrides[$overrideKey];
            } else {
                $getter = 'get' . $field;
                $values = $emailLog->{$getter}();
            }

            $values = \OpenDxp\Helper\Mail::parseEmailAddressField($values);

            if ($values) {
                [$value] = $values;
                $mail->{'add' . $field}(new Address($value['email'], $value['name']));
            }
        }

        $mail->subject($emailLog->getSubject());

        if ($emailLog->getDocumentId()) {
            $mail->setDocument($emailLog->getDocumentId());
        }

        try {
            $params = $emailLog->getParams();
        } catch (\Exception) {
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
