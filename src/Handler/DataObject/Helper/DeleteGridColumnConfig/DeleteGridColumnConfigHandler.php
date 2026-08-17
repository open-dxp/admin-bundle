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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DeleteGridColumnConfig;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Bundle\AdminBundle\Resolver\Grid\DataObjectGridColumnConfigResolver;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Config;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeleteGridColumnConfigHandler
{
    public function __construct(
        private readonly DataObjectGridColumnConfigResolver $gridConfigResolver,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Config $config,
        private readonly RequestStack $requestStack,
        private readonly AdminUserContextInterface $userContext,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {
    }

    public function __invoke(DeleteGridColumnConfigPayload $payload): DeleteGridColumnConfigResult
    {
        if ($payload->gridConfigId !== null) {
            $adminUser = $this->userContext->getAdminUser();
            $gridConfig = GridConfig::getById($payload->gridConfigId);
            if (!$gridConfig) {
                throw new AdminOperationFailedException('Grid config not found: ' . $payload->gridConfigId);
            }
            if ($gridConfig->getOwnerId() !== $adminUser->getId() && !$adminUser->isAdmin()) {
                throw new BadRequestHttpException("don't mess with someone elses grid config");
            }
            $gridConfig->delete();
        }

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

        $config = $this->gridConfigResolver->resolve($payload->locale, $params, true);
        $data = [...$config->toArray(), 'deleteSuccess' => true];

        $event = new GenericEvent($this->currentControllerContext->getController(), [
            'data'    => $data,
            'request' => $this->requestStack->getCurrentRequest(),
            'config'  => $this->config,
            'context' => 'delete',
        ]);
        $this->eventDispatcher->dispatch($event, AdminEvents::OBJECT_GRID_GET_COLUMN_CONFIG_PRE_SEND_DATA);

        return new DeleteGridColumnConfigResult($event->getArgument('data'));
    }
}
