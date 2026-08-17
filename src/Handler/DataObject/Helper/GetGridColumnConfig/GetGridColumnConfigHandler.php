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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\GetGridColumnConfig;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Resolver\Grid\DataObjectGridColumnConfigResolver;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Config;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetGridColumnConfigHandler
{
    public function __construct(
        private readonly DataObjectGridColumnConfigResolver $gridConfigResolver,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Config $config,
        private readonly RequestStack $requestStack,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {}

    public function __invoke(GetGridColumnConfigPayload $payload): GetGridColumnConfigResult
    {
        $params = [
            'id'              => $payload->id,
            'objectId'        => $payload->objectId,
            'name'            => $payload->name,
            'type'            => $payload->type,
            'types'           => $payload->types,
            'gridtype'        => $payload->gridtype,
            'gridConfigId'    => $payload->gridConfigId,
            'searchType'      => $payload->searchType,
            'noSystemColumns' => $payload->noSystemColumns,
            'noBrickColumns'  => $payload->noBrickColumns,
        ];

        $config = $this->gridConfigResolver->resolve($payload->locale, $params);

        $event = new GenericEvent($this->currentControllerContext->getController(), [
            'data'    => $config->toArray(),
            'request' => $this->requestStack->getCurrentRequest(),
            'config'  => $this->config,
            'context' => 'get',
        ]);
        $this->eventDispatcher->dispatch($event, AdminEvents::OBJECT_GRID_GET_COLUMN_CONFIG_PRE_SEND_DATA);

        return new GetGridColumnConfigResult($event->getArgument('data'));
    }
}
