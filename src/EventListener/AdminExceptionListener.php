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

namespace OpenDxp\Bundle\AdminBundle\EventListener;

use Doctrine\DBAL\Exception as DBALException;
use Exception;
use OpenDxp;
use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Model\Element\ValidationException;
use OpenDxp\Model\Exception\ConfigWriteException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * @internal
 */
class AdminExceptionListener implements EventSubscriberInterface
{
    use OpenDxpContextAwareTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $ex = $event->getThrowable();

        if ($this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_ADMIN)) {
            // only return JSON error for XHR requests
            if (!$request->isXmlHttpRequest()) {
                return;
            }

            [$code, $headers, $message] = $this->getResponseData($ex);

            $data = [
                'success' => false,
                'traceString' => '',
            ];

            // DBAL exceptions do include SQL statements, we don't want to expose them
            if (!OpenDxp::inDebugMode() && $ex instanceof DBALException) {
                $message = 'Database error, see logs for details';
            }

            if (OpenDxp::inDebugMode()) {
                $data['trace'] = $ex->getTrace();
                $data['traceString'] = 'in ' . $ex->getFile() . ':' . $ex->getLine() . "\n" . $ex->getTraceAsString();
            }

            if ($ex instanceof ValidationException) {
                $data['type'] = 'ValidationException';
                $code = 422;

                $this->recursiveAddValidationExceptionSubItems($ex->getSubItems(), $message, $data['traceString']);
            }

            if ($ex instanceof ConfigWriteException) {
                $data['type'] = 'ConfigWriteException';
                $code = 422;
            }

            $data['message'] = $message;

            $response = new JsonResponse($data, $code, $headers);
            $event->setResponse($response);
        }
    }

    private function getResponseData(Throwable $ex, int $defaultStatusCode = 500): array
    {
        $code = $defaultStatusCode;
        $headers = [];

        $message = $ex->getMessage();

        if ($ex instanceof HttpExceptionInterface) {
            if (empty($message)) {
                $message = Response::$statusTexts[$ex->getStatusCode()];
            }

            $code = $ex->getStatusCode();
            $headers = $ex->getHeaders();
        }

        return [$code, $headers, $message];
    }

    /**
     * @param Exception[] $items
     */
    protected function recursiveAddValidationExceptionSubItems(array $items, string &$message, string &$detailedInfo): void
    {
        if (!$items) {
            return;
        }
        foreach ($items as $e) {
            if ($e->getMessage()) {
                $message .= '<b>' . $e->getMessage() . '</b>';
                if ($e instanceof ValidationException) {
                    $this->addContext($e, $message);
                }
                $message .= '<br>';

                $detailedInfo .= '<br><b>Message:</b><br>';
                $detailedInfo .= $e->getMessage() . '<br>';

                $inner = $this->getInnerStack($e);
                $detailedInfo .= '<br><b>Trace:</b> ' . $inner->getTraceAsString() . '<br>';
            }

            if ($e instanceof ValidationException) {
                $this->recursiveAddValidationExceptionSubItems($e->getSubItems(), $message, $detailedInfo);
            }
        }
    }

    protected function addContext(ValidationException $e, string &$message): void
    {
        $contextStack = $e->getContextStack();
        if ($contextStack) {
            $message = $message . ' (' . implode(',', $contextStack) . ')';
        }
    }

    protected function getInnerStack(Throwable $e): Throwable
    {
        while ($e->getPrevious()) {
            $e = $e->getPrevious();
        }

        return $e;
    }
}
