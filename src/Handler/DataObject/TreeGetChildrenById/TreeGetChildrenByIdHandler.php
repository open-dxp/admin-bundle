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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\TreeGetChildrenById;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectChildren\GetDataObjectChildrenHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectChildrenResult;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TreeGetChildrenByIdHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceInterface $elementService,
        private readonly GetDataObjectChildrenHandler $childrenHandler,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(TreeGetChildrenByIdPayload $payload): GetDataObjectChildrenResult
    {
        $node = $payload->node;
        $filter = $payload->filter;
        $start = $payload->start;
        $limit = $payload->limit;
        $view = $payload->view;
        $fromPaging = $payload->fromPaging;
        $requestQueryAll = $payload->allParams;

        $object = DataObject::getById($node);
        $objectTypes = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER];

        if ($object instanceof DataObject\Concrete) {
            $class = $object->getClass();
            if ($class->getShowVariants()) {
                $objectTypes = DataObject::$types;
            }
        }

        $objects = [];
        $offset = $total = $filteredTotalCount = 0;

        if ($object->hasChildren($objectTypes)) {
            $offset = $start;

            $filterForCondition = $filter;
            $effectiveLimit = $limit;
            if (!is_null($filterForCondition)) {
                if (!str_ends_with($filterForCondition, '*')) {
                    $filterForCondition .= '*';
                }
                $filterForCondition = str_replace('*', '%', $filterForCondition);
                $effectiveLimit = 100;
            }

            $childrenList = new DataObject\Listing();
            $childrenList->setCondition($this->buildChildrenCondition($object, $filterForCondition, $view));
            $childrenList->setLimit($effectiveLimit);
            $childrenList->setOffset($offset);

            if ($object->getChildrenSortBy() === 'index') {
                $childrenList->setOrderKey('objects.index ASC', false);
            } else {
                $childrenList->setOrderKey(
                    sprintf(
                        'CAST(objects.%s AS CHAR CHARACTER SET utf8) COLLATE utf8_general_ci %s',
                        $object->getChildrenSortBy(), $object->getChildrenSortOrder()
                    ),
                    false
                );
            }
            $childrenList->setObjectTypes($objectTypes);

            $cv = $view ? ($this->elementService->getCustomViewById($view) ?? []) : [];
            Element\Service::addTreeFilterJoins($cv, $childrenList);

            $beforeListLoadEvent = new GenericEvent($this, [
                'list' => $childrenList,
                'context' => $requestQueryAll,
            ]);
            $this->eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::OBJECT_LIST_BEFORE_LIST_LOAD);

            /** @var DataObject\Listing $childrenList */
            $childrenList = $beforeListLoadEvent->getArgument('list');

            $result = ($this->childrenHandler)(
                object: $object,
                childrenList: $childrenList,
                view: $view,
                filter: $filter,
                limit: $limit,
                offset: $offset,
                fromPaging: $fromPaging,
                objectTypes: $objectTypes,
            );

            $objects = $result->objects;
            $offset = $result->offset;
            $limit = $result->limit;
            $total = $result->total;
            $filteredTotalCount = $result->filteredTotalCount;
            $filter = $result->filter;
        }

        $event = new GenericEvent($this, ['objects' => $objects]);
        $this->eventDispatcher->dispatch($event, AdminEvents::OBJECT_TREE_GET_CHILDREN_BY_ID_PRE_SEND_DATA);
        $objects = $event->getArgument('objects');

        return new GetDataObjectChildrenResult(
            objects: $objects,
            offset: $offset,
            limit: $limit,
            total: $total,
            filteredTotalCount: $filteredTotalCount,
            filter: $filter,
            fromPaging: $fromPaging,
        );
    }

    private function buildChildrenCondition(DataObject\AbstractObject $object, ?string $filter, ?string $view): string
    {
        $condition = "objects.parentId = '" . $object->getId() . "'";

        if ($view) {
            $cv = $this->elementService->getCustomViewById($view);

            if (!empty($cv['classes'])) {
                $cvConditions = [];
                $cvClasses = $cv['classes'];
                foreach ($cvClasses as $key => $cvClass) {
                    $cvConditions[] = "objects.classId = '" . $key . "'";
                }

                $cvConditions[] = "objects.type = 'folder'";
                $condition .= ' AND (' . implode(' OR ', $cvConditions) . ')';
            }
        }

        $adminUser = $this->userContext->getAdminUser();
        if (!$adminUser->isAdmin()) {
            $userIds = $adminUser->getRoles();
            $currentUserId = $adminUser->getId();
            $userIds[] = $currentUserId;

            $inheritedPermission = $object->getDao()->isInheritingPermission('list', $userIds);

            $anyAllowedRowOrChildren = 'EXISTS(SELECT list FROM users_workspaces_object uwo WHERE userId IN (' . implode(',', $userIds) . ') AND list=1 AND LOCATE(CONCAT(objects.path,objects.key),cpath)=1 AND
                NOT EXISTS(SELECT list FROM users_workspaces_object WHERE userId =' . $currentUserId . '  AND list=0 AND cpath = uwo.cpath))';
            $isDisallowedCurrentRow = 'EXISTS(SELECT list FROM users_workspaces_object WHERE userId IN (' . implode(',', $userIds) . ')  AND cid = objects.id AND list=0)';

            $condition .= ' AND IF(' . $anyAllowedRowOrChildren . ',1,IF(' . $inheritedPermission . ', ' . $isDisallowedCurrentRow . ' = 0, 0)) = 1';
        }

        if (!is_null($filter)) {
            $db = Db::get();
            $condition .= ' AND CAST(objects.key AS CHAR CHARACTER SET utf8) COLLATE utf8_general_ci LIKE ' . $db->quote($filter);
        }

        return $condition;
    }
}
