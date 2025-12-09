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

use OpenDxp\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use OpenDxp\Http\Request\Resolver\DocumentResolver;
use OpenDxp\Http\Request\Resolver\EditmodeResolver;
use OpenDxp\Http\Request\Resolver\OutputTimestampResolver;
use OpenDxp\Http\RequestHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class EnablePreviewTimeSliderListener implements EventSubscriberInterface
{
    use ResponseInjectionTrait;

    public function __construct(
        protected OutputTimestampResolver $outputTimestampResolver,
        protected RequestHelper $requestHelper,
        protected EditmodeResolver $editmodeResolver,
        protected DocumentResolver $documentResolver
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->outputTimestampResolver->timestampWasQueried()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->editmodeResolver->isEditmode($request)) {
            return;
        }

        if (!$this->requestHelper->isFrontendRequestByAdmin($request)) {
            return;
        }

        $response = $event->getResponse();
        if (!$this->isHtmlResponse($response)) {
            return;
        }

        $documentId = 0;
        $document = $this->documentResolver->getDocument($request);
        if ($document) {
            $documentId = $document->getId();
        }

        $code = '
            <script>
                var documentId = ' . $documentId . ";
                var documentTab = top.opendxp.globalmanager.get('document_' + documentId);
                if(documentTab && documentTab.preview) {
                    documentTab.preview.showTimeSlider();
                }
            </script>
        ";

        $this->injectBeforeHeadEnd($response, $code);
    }
}
