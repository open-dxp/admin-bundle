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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Http\RequestHelper;
use OpenDxp\Logger;
use OpenDxp\Mail;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Tool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/email')]
class EmailController extends AdminAbstractController
{
    /**
     * @throws \Exception
     */
    #[Route('/email-logs', name: 'opendxp_admin_email_emaillogs', methods: ['GET', 'POST'])]
    public function emailLogsAction(Request $request): JsonResponse
    {
        if (!$this->getAdminUser()->isAllowed('emails') && !$this->getAdminUser()->isAllowed('gdpr_data_extractor')) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $list = new Tool\Email\Log\Listing();
        if ($request->get('documentId')) {
            $list->setCondition('documentId = ' . (int)$request->get('documentId'));
        }
        $list->setLimit((int)$request->get('limit', 50));
        $list->setOffset((int)$request->get('start', 0));
        $list->setOrderKey('sentDate');

        if ($request->get('filter')) {
            $filterTerm = $request->get('filter');
            if ($filterTerm === '*') {
                $filterTerm = '';
            }

            $filterTerm = str_replace('%', '*', $filterTerm);
            $filterTerm = htmlspecialchars($filterTerm, ENT_QUOTES);

            if (strpos($filterTerm, '@')) {
                $parts = explode(' ', $filterTerm);
                $parts = array_map(static function ($part) {
                    if (strpos($part, '@')) {
                        $part = '"' . $part . '"';
                    }

                    return $part;
                }, $parts);
                $filterTerm = implode(' ', $parts);
            }

            if (str_starts_with($filterTerm, '@')) {
                $filterTerm = str_replace('@', '', $filterTerm);
            }

            $condition = '( MATCH (`from`,`to`,`cc`,`bcc`,`subject`,`params`) AGAINST (' . $list->quote($filterTerm) . ' IN BOOLEAN MODE) )';

            if ($request->get('documentId')) {
                $condition .= 'AND documentId = ' . (int)$request->get('documentId');
            }

            $list->setCondition($condition);
        }

        $list->setOrder('DESC');

        $data = $list->load();
        $jsonData = [];

        foreach ($data as $entry) {
            $tmp = $entry->getObjectVars();
            unset($tmp['bodyHtml'], $tmp['bodyText']);
            $jsonData[] = $tmp;
        }

        return $this->adminJson([
            'data' => $jsonData,
            'success' => true,
            'total' => $list->getTotalCount(),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/show-email-log', name: 'opendxp_admin_email_showemaillog', methods: ['GET'])]
    public function showEmailLogAction(Request $request, ?Profiler $profiler): JsonResponse|Response
    {
        if ($profiler) {
            $profiler->disable();
        }

        if (!$this->getAdminUser()->isAllowed('emails')) {
            throw $this->createAccessDeniedHttpException("Permission denied, user needs 'emails' permission.");
        }

        $type = $request->get('type');
        $emailLog = Tool\Email\Log::getById((int) $request->get('id'));

        if (!$emailLog) {
            throw $this->createNotFoundException();
        }

        if ($type === 'text') {
            return $this->render('@OpenDxpAdmin/admin/email/text.html.twig', ['log' => $emailLog->getTextLog()]);
        }

        if ($type === 'html') {
            return new Response($emailLog->getHtmlLog(), 200, [
                'Content-Security-Policy' => "default-src 'self'; style-src 'self' 'unsafe-inline'; img-src * data:",
            ]);
        }

        if ($type === 'params') {
            try {
                $params = $emailLog->getParams();
            } catch (\Exception $e) {
                Logger::warning('Could not decode JSON param string');
                $params = [];
            }
            foreach ($params as &$entry) {
                $this->enhanceLoggingData($entry);
            }

            return $this->adminJson($params);
        }

        if ($type === 'details') {
            $data = $emailLog->getObjectVars();

            return $this->adminJson($data);
        }

        return new Response('No Type specified');

    }

    protected function enhanceLoggingData(?array &$data, ?array &$fullEntry = null): void
    {
        if (!is_array($data)) {
            return;
        }

        if (!empty($data['objectClass'])) {
            $class = '\\' . ltrim($data['objectClass'], '\\');
            $reflection = new \ReflectionClass($class);

            if (!empty($data['objectId']) && $reflection->implementsInterface(ElementInterface::class)) {
                $obj = $class::getById($data['objectId']);
                if (is_null($obj)) {
                    $data['objectPath'] = '';
                } else {
                    $data['objectPath'] = $obj->getRealFullPath();
                }

                //check for classmapping
                if (stristr($class, '\\OpenDxp\\Model') === false) {
                    $niceClassName = '\\' . ltrim($reflection->getParentClass()->getName(), '\\');
                } else {
                    $niceClassName = $class;
                }

                $niceClassName = str_replace(['\\OpenDxp\\Model\\', '_'], ['', '\\'], $niceClassName);

                $tmp = explode('\\', $niceClassName);
                if (in_array($tmp[0], ['DataObject', 'Document', 'Asset'])) {
                    $data['objectClassBase'] = $tmp[0];
                    $data['objectClassSubType'] = $tmp[1];
                }
            }
        }

        foreach ($data as &$value) {

            if (!is_array($value)) {
                continue;
            }

            $this->enhanceLoggingData($value, $data);
        }

        unset($value);

        if ($data['children'] ?? false) {
            foreach ($data['children'] as $key => $entry) {
                if (is_string($key)) { //key must be integers
                    unset($data['children'][$key]);
                }
            }
            $data['iconCls'] = 'opendxp_icon_folder';
            $data['data'] = ['type' => 'simple', 'value' => 'Children (' . count($data['children']) . ')'];
        } else {
            //setting the icon class
            if (empty($data['iconCls'])) {
                if (($data['objectClassBase'] ?? '') === 'DataObject') {
                    $fullEntry['iconCls'] = 'opendxp_icon_object';
                } elseif (($data['objectClassBase'] ?? '') === 'Asset') {
                    switch ($data['objectClassSubType']) {
                        case 'Image':
                            $fullEntry['iconCls'] = 'opendxp_icon_image';

                            break;
                        case 'Video':
                            $fullEntry['iconCls'] = 'opendxp_icon_wmv';

                            break;
                        case 'Text':
                            $fullEntry['iconCls'] = 'opendxp_icon_txt';

                            break;
                        case 'Document':
                            $fullEntry['iconCls'] = 'opendxp_icon_pdf';

                            break;
                        default:
                            $fullEntry['iconCls'] = 'opendxp_icon_asset';
                    }
                } elseif (strpos($data['objectClass'] ?? '', 'Document') === 0) {
                    $fullEntry['iconCls'] = 'opendxp_icon_' . strtolower($data['objectClassSubType']);
                } else {
                    $data['iconCls'] = 'opendxp_icon_text';
                }
            }

            $data['leaf'] = true;
        }
    }

    /**
     * @throws \Exception
     */
    #[Route('/delete-email-log', name: 'opendxp_admin_email_deleteemaillog', methods: ['DELETE'])]
    public function deleteEmailLogAction(Request $request): JsonResponse
    {
        if (!$this->getAdminUser()->isAllowed('emails')) {
            throw $this->createAccessDeniedHttpException("Permission denied, user needs 'emails' permission.");
        }

        $success = false;
        $emailLog = Tool\Email\Log::getById((int) $request->get('id'));
        if ($emailLog instanceof Tool\Email\Log) {
            $emailLog->delete();
            $success = true;
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/resend-email', name: 'opendxp_admin_email_resendemail', methods: ['POST'])]
    public function resendEmailAction(Request $request): JsonResponse
    {
        if (!$this->getAdminUser()->isAllowed('emails')) {
            throw $this->createAccessDeniedHttpException("Permission denied, user needs 'emails' permission.");
        }

        $success = false;
        $emailLog = Tool\Email\Log::getById((int) $request->get('id'));

        if ($emailLog instanceof Tool\Email\Log) {
            $mail = new Mail();
            $mail->preventDebugInformationAppending();
            $mail->setIgnoreDebugMode(true);

            if (!empty($request->get('to'))) {
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
                if (!$values = $request->get(strtolower($field))) {
                    $getter = 'get' . $field;
                    $values = $emailLog->{$getter}();
                }

                $values = \OpenDxp\Helper\Mail::parseEmailAddressField($values);

                if ($values) {
                    [$value] = $values;
                    $prefix = 'add';
                    $mail->{$prefix . $field}(new Address($value['email'], $value['name']));
                }
            }

            $mail->subject($emailLog->getSubject());

            // add document
            if ($emailLog->getDocumentId()) {
                $mail->setDocument($emailLog->getDocumentId());
            }

            // re-add params
            try {
                $params = $emailLog->getParams();
            } catch (\Exception $e) {
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
            $success = true;
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/send-test-email', name: 'opendxp_admin_email_sendtestemail', methods: ['POST'])]
    public function sendTestEmailAction(Request $request): JsonResponse
    {
        if (!$this->getAdminUser()->isAllowed('emails')) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        // Simulate a frontend request to prefix assets
        $request->attributes->set(RequestHelper::ATTRIBUTE_FRONTEND_REQUEST, true);

        $mail = new Mail();

        if ($request->get('emailType') === 'text') {
            $mail->text(strip_tags($request->get('content')));
        } elseif ($request->get('emailType') === 'html') {
            $mail->html($request->get('content'));
        } elseif ($request->get('emailType') === 'document') {
            $doc = \OpenDxp\Model\Document::getByPath($request->get('documentPath'));

            if ($doc instanceof \OpenDxp\Model\Document\Email) {
                $mail->setDocument($doc);

                if ($request->get('mailParamaters')) {
                    if ($mailParamsArray = json_decode($request->get('mailParamaters'), true)) {
                        foreach ($mailParamsArray as $mailParam) {
                            if ($mailParam['key']) {
                                $mail->setParam($mailParam['key'], $mailParam['value']);
                            }
                        }
                    }
                }
            } else {
                throw new \Exception('Email document not found!');
            }
        }

        if ($from = $request->get('from')) {
            $addressArray = \OpenDxp\Helper\Mail::parseEmailAddressField($from);
            if ($addressArray) {
                //use the first address only
                [$cleanedFromAddress] = $addressArray;
                $mail->from(new Address($cleanedFromAddress['email'], $cleanedFromAddress['name']));
            }
        }

        $toAddresses = \OpenDxp\Helper\Mail::parseEmailAddressField($request->get('to'));
        foreach ($toAddresses as $cleanedToAddress) {
            $mail->addTo($cleanedToAddress['email'], $cleanedToAddress['name']);
        }

        $mail->subject($request->get('subject'));
        $mail->setIgnoreDebugMode(true);

        $mail->send();

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/blocklist', name: 'opendxp_admin_email_blocklist', methods: ['POST'])]
    public function blocklistAction(Request $request): JsonResponse
    {
        if (!$this->getAdminUser()->isAllowed('emails')) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            if (is_array($data)) {
                foreach ($data as $key => &$value) {
                    if (is_string($value)) {
                        if ($key === 'address') {
                            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                        }

                        $value = trim($value);
                    }
                }
            }

            if ($request->get('xaction') === 'destroy') {
                $address = Tool\Email\Blocklist::getByAddress($data['address']);
                $address->delete();

                return $this->adminJson(['success' => true, 'data' => []]);
            }

            if ($request->get('xaction') === 'update') {
                $address = Tool\Email\Blocklist::getByAddress($data['address']);
                $address->setValues($data);
                $address->save();

                return $this->adminJson(['data' => $address->getObjectVars(), 'success' => true]);
            }

            if ($request->get('xaction') === 'create') {
                unset($data['id']);

                $address = new Tool\Email\Blocklist();
                $address->setValues($data);
                $address->save();

                return $this->adminJson(['data' => $address->getObjectVars(), 'success' => true]);
            }
        } else {
            // get list of routes

            $list = new Tool\Email\Blocklist\Listing();

            $list->setLimit((int) $request->get('limit', 50));
            $list->setOffset((int) $request->get('start', 0));

            $sortingSettings = \OpenDxp\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings($request->query->all());
            if ($sortingSettings['orderKey']) {
                $orderKey = $sortingSettings['orderKey'];
            }
            if ($sortingSettings['order']) {
                $order = $sortingSettings['order'];
            }

            if ($request->get('filter')) {
                $list->setCondition('`address` LIKE ' . $list->quote('%'.$request->get('filter').'%'));
            }

            $data = $list->load();
            $jsonData = [];
            foreach ($data as $entry) {
                $jsonData[] = $entry->getObjectVars();
            }

            return $this->adminJson([
                'success' => true,
                'data' => $jsonData,
                'total' => $list->getTotalCount(),
            ]);
        }

        return $this->adminJson(['success' => false]);
    }

    protected function parseLoggingParamObject(array $params): mixed
    {
        $data = null;
        if ($params['data']['type'] === 'object') {
            $class = '\\' . ltrim($params['data']['objectClass'], '\\');
            $reflection = new \ReflectionClass($class);

            if (!empty($params['data']['objectId']) && $reflection->implementsInterface(ElementInterface::class)) {
                $obj = $class::getById($params['data']['objectId']);
                if (!is_null($obj)) {
                    $data = $obj;
                }
            }
        } else {
            $data = $params['data']['value'];
        }

        return $data;
    }
}
