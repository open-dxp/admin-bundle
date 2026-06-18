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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\GridProxy;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetGridService;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GridProxyHandler
{
    public function __construct(
        private readonly AssetGridService $assetGridService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(GridProxyPayload $payload): GridProxyResult
    {
        $params = $payload->params;

        $filterPrepareEvent = new GenericEvent(null, ['requestParams' => $params]);
        $this->eventDispatcher->dispatch($filterPrepareEvent, AdminEvents::ASSET_LIST_BEFORE_FILTER_PREPARE);
        $params = $filterPrepareEvent->getArgument('requestParams');

        return new GridProxyResult(
            $this->assetGridService->gridProxy($params, $payload->language)
        );
    }
}
