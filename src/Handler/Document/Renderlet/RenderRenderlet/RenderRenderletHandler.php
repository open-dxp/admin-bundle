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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Renderlet\RenderRenderlet;

use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Document\Editable\EditableHandler;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Templating\Renderer\ActionRenderer;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class RenderRenderletHandler
{
    public function __construct(
        private readonly ActionRenderer $actionRenderer,
        private readonly EditableHandler $editableHandler,
        private readonly LocaleServiceInterface $localeService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {
    }

    public function __invoke(RenderRenderletPayload $payload): RenderRenderletResult
    {
        $element = null;
        if ($payload->id && $payload->type) {
            $element = Service::getElementById($payload->type, $payload->id);
        }

        if (!$element instanceof ElementInterface) {
            throw new NotFoundHttpException(sprintf('Element with type %s and ID %d was not found', $payload->type ?? 'null', $payload->id ?? 0));
        }

        if (!$element->isAllowed('view')) {
            throw new AccessDeniedHttpException(sprintf('Access to element with type %s and ID %d is not allowed', $payload->type, $payload->id));
        }

        $this->eventDispatcher->dispatch(
            new GenericEvent(
                $this->currentControllerContext->getController(),
                [
                    'requestParams' => $payload->query,
                    'element'       => $element,
                ]
            ),
            DocumentEvents::EDITABLE_RENDERLET_PRE_RENDER,
        );

        $attributes = [];

        if ($payload->parentDocumentId) {
            $document = Document\PageSnippet::getById((int) $payload->parentDocumentId);
            if ($document) {
                $attributes = $this->actionRenderer->addDocumentAttributes($document, $attributes);
                unset($attributes[DynamicRouter::CONTENT_TEMPLATE]);
            }
        }

        if ($payload->template) {
            $attributes[DynamicRouter::CONTENT_TEMPLATE] = $payload->template;
        }

        $query = $payload->query;
        foreach (['controller', 'action', 'module', 'bundle'] as $key) {
            unset($query[$key]);
        }

        if (isset($attributes['_locale'])) {
            $this->localeService->setLocale($attributes['_locale']);
        }

        return new RenderRenderletResult(
            html: $this->editableHandler->renderAction($payload->controller, $attributes, $query),
        );
    }
}
