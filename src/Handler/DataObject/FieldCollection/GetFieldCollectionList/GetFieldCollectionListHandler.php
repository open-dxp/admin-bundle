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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionList;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Model\DataObject;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetFieldCollectionListHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {
    }

    public function __invoke(GetFieldCollectionListPayload $payload): FieldCollectionListResult
    {
        $allowedTypes = $payload->allowedTypes;
        $fieldName = $payload->fieldName;
        $objectId = $payload->objectId;
        $layoutId = $payload->layoutId;
        $adminUser = $this->userContext->getAdminUser();
        $list = (new DataObject\Fieldcollection\Definition\Listing())->load();
        $currentLayoutId = $layoutId;

        if ($allowedTypes !== null) {
            $filteredList = [];
            $object = DataObject\Concrete::getById($objectId);

            foreach ($list as $type) {
                if (!in_array($type->getKey(), $allowedTypes)) {
                    continue;
                }

                $filteredList[] = $type;

                $layoutDefinitions = $type->getLayoutDefinitions();
                $context = [
                    'containerType' => 'fieldcollection',
                    'containerKey' => $type->getKey(),
                    'outerFieldname' => $fieldName,
                ];

                DataObject\Service::enrichLayoutDefinition($layoutDefinitions, $object, $context);

                if ($currentLayoutId == -1 && $adminUser->isAdmin()) {
                    DataObject\Service::createSuperLayout($layoutDefinitions);
                }
            }

            $list = $filteredList;
        }

        $event = new GenericEvent($this->currentControllerContext->getController(), ['list' => $list, 'objectId' => $objectId]);
        $this->eventDispatcher->dispatch($event, AdminEvents::CLASS_FIELDCOLLECTION_LIST_PRE_SEND_DATA);

        return new FieldCollectionListResult($event->getArgument('list'));
    }
}
