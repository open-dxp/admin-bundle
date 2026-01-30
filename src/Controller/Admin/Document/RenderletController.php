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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Document;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Document\Editable\EditableHandler;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Templating\Renderer\ActionRenderer;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class RenderletController extends AdminAbstractController
{
    /**
     * Handles editmode preview for renderlets
     */
    #[Route('/document_tag/renderlet', name: 'opendxp_admin_document_renderlet_renderlet', methods: ['GET'])]
    public function renderletAction(
        Request $request,
        ActionRenderer $actionRenderer,
        EditableHandler $editableHandler,
        LocaleServiceInterface $localeService,
        EventDispatcherInterface $eventDispatcher
    ): Response {

        $query = $request->query->all();
        $attributes = [];

        // load element to make sure the request is valid
        $element = $this->loadElement($request);

        $event = new GenericEvent($this, [
            'requestParams' => $query,
            'element' => $element,
        ]);

        $eventDispatcher->dispatch($event, DocumentEvents::EDITABLE_RENDERLET_PRE_RENDER);

        $controller = $request->query->get('controller');

        // set document if set in request
        if ($documentId = $request->query->get('opendxp_parentDocument')) {
            $document = Document\PageSnippet::getById((int) $documentId);
            if ($document) {
                $attributes = $actionRenderer->addDocumentAttributes($document, $attributes);
                unset($attributes[DynamicRouter::CONTENT_TEMPLATE]);
            }
        }

        // override template if set
        if ($template = $request->query->get('template')) {
            $attributes[DynamicRouter::CONTENT_TEMPLATE] = $template;
        }

        foreach (['controller', 'action', 'module', 'bundle'] as $key) {
            if (isset($query[$key])) {
                unset($query[$key]);
            }
        }

        // setting locale manually here before rendering the action to make sure editables use the right locale - if this
        // is needed in multiple places, move this to the tag handler instead (see #1834)
        if (isset($attributes['_locale'])) {
            $localeService->setLocale($attributes['_locale']);
        }

        $result = $editableHandler->renderAction($controller, $attributes, $query);

        return new Response($result);
    }

    private function loadElement(Request $request): ElementInterface
    {
        $element = null;

        $id = $request->query->get('id');
        $type = $request->query->get('type');

        if ($id && $type) {
            $element = Service::getElementById($type, (int)$id);
        }

        if (!$element instanceof ElementInterface) {
            throw $this->createNotFoundException(sprintf('Element with type %s and ID %d was not found', $type ?: 'null', $id ?: 'null'));
        }

        if (!$element->isAllowed('view')) {
            throw $this->createAccessDeniedException(sprintf('Access to element with type %s and ID %d is not allowed', $type, $id));
        }

        return $element;
    }
}
