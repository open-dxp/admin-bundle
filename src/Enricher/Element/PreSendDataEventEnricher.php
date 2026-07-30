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

namespace OpenDxp\Bundle\AdminBundle\Enricher\Element;

use LogicException;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service as ElementService;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class PreSendDataEventEnricher
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {}

    /**
     * Dispatches the AdminEvents::*_GET_PRE_SEND_DATA event matching the element's type
     */
    public function enrich(ElementInterface $element, array &$data): void
    {
        $elementType = ElementService::getElementType($element);

        $eventName = match ($elementType) {
            'document' => AdminEvents::DOCUMENT_GET_PRE_SEND_DATA,
            'asset' => AdminEvents::ASSET_GET_PRE_SEND_DATA,
            'object' => AdminEvents::OBJECT_GET_PRE_SEND_DATA,
            default => throw new LogicException(sprintf('No GET_PRE_SEND_DATA event known for element class "%s".', $element::class)),
        };

        $event = new GenericEvent($this->currentControllerContext->getController(), [
            'data'       => $data,
            $elementType => $element,
        ]);

        $this->eventDispatcher->dispatch($event, $eventName);
        $eventData = $event->getArgument('data');

        $data = is_array($eventData) ? $eventData : $data;
    }
}