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

namespace OpenDxp\Bundle\AdminBundle\EventListener;

use OpenDxp\Bundle\AdminBundle\Attribute\AsHtmlContentTypeResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Forces Content-Type: text/html on every response - success, a direct failure return, or a
 * thrown exception alike - for any controller action marked #[AsHtmlContentTypeResponse].
 * Centralizes the Ext.form.Action.Submit (hidden-iframe upload) content-type workaround so no
 * individual action has to set the header itself.
 *
 * @internal
 */
class HtmlContentTypeResponseListener implements EventSubscriberInterface
{
    private const string REQUEST_ATTRIBUTE = '_html_content_type_response';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => 'onKernelControllerArguments',
            KernelEvents::RESPONSE             => 'onKernelResponse',
        ];
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if ($event->getAttributes(AsHtmlContentTypeResponse::class) !== []) {
            $event->getRequest()->attributes->set(self::REQUEST_ATTRIBUTE, true);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($event->getRequest()->attributes->get(self::REQUEST_ATTRIBUTE) === true) {
            $event->getResponse()->headers->set('Content-Type', 'text/html');
        }
    }
}
