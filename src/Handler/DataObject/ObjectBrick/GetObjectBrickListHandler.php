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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class GetObjectBrickListHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,private readonly EventDispatcherInterface $eventDispatcher) {}

    public function __invoke(
        ?string $classId,
        ?string $fieldName,
        ?string $layoutId,
        int $objectId,
    ): ObjectBrickListResult {
        $adminUser = $this->userContext->getAdminUser();
        $list = (new DataObject\Objectbrick\Definition\Listing())->load();

        if ($classId !== null && $fieldName !== null) {
            $filteredList = [];
            $className = DataObject\ClassDefinition::getById($classId)->getName();

            foreach ($list as $type) {
                $clsDefs = $type->getClassDefinitions();
                if (!empty($clsDefs)) {
                    foreach ($clsDefs as $cd) {
                        if ($cd['classname'] == $className && $cd['fieldname'] == $fieldName) {
                            $filteredList[] = $type;
                            break;
                        }
                    }
                }

                $layout = $type->getLayoutDefinitions();
                if ($layoutId == -1 && $adminUser->isAdmin()) {
                    DataObject\Service::createSuperLayout($layout);
                }

                $context = [
                    'containerType' => 'objectbrick',
                    'containerKey' => $type->getKey(),
                    'outerFieldname' => $fieldName,
                ];

                $object = DataObject\Concrete::getById($objectId);
                DataObject\Service::enrichLayoutDefinition($layout, $object, $context);
                $type->setLayoutDefinitions($layout);
            }

            $list = $filteredList;
        }

        $event = new GenericEvent(null, ['list' => $list, 'objectId' => $objectId]);
        $this->eventDispatcher->dispatch($event, AdminEvents::CLASS_OBJECTBRICK_LIST_PRE_SEND_DATA);

        return new ObjectBrickListResult($event->getArgument('list'));
    }
}
