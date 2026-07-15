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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\TreeGetDataObjectChildren;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Exception\DataObject\DataObjectNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TreeGetDataObjectChildrenHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceInterface $elementService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(TreeGetDataObjectChildrenPayload $payload): TreeGetDataObjectChildrenPaginatedResult|TreeGetDataObjectChildrenListResult
    {
        $node = $payload->node;
        $filter = $payload->filter;
        $start = $payload->start;
        $limit = $payload->limit;
        $view = $payload->view;
        $fromPaging = $payload->fromPaging;
        $requestQueryAll = $payload->allParams;

        $object = DataObject::getById($node) ?? throw new DataObjectNotFoundException($node);
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

            [$objects, $total, $filteredTotalCount] = $this->loadChildren($object, $childrenList, $view);
            $limit = $effectiveLimit;
        }

        $event = new GenericEvent($this, ['objects' => $objects]);
        $this->eventDispatcher->dispatch($event, AdminEvents::OBJECT_TREE_GET_CHILDREN_BY_ID_PRE_SEND_DATA);
        $objects = $event->getArgument('objects');

        if (!$payload->hasPagination()) {
            return new TreeGetDataObjectChildrenListResult($objects);
        }

        return new TreeGetDataObjectChildrenPaginatedResult(
            nodes: $objects,
            offset: $offset,
            limit: $limit,
            total: $total,
            overflow: !is_null($filter) && ($filteredTotalCount > $limit),
            fromPaging: $fromPaging,
            filter: $filter ?? '',
            inSearch: $payload->inSearch,
        );
    }

    private function loadChildren(DataObject\AbstractObject $object, DataObject\Listing $childrenList, string $view): array
    {
        $adminUser = $this->userContext->getAdminUser();
        $objects = [];

        $cv = $view ? ($this->elementService->getCustomViewById($view) ?? []) : [];

        $children = $childrenList->load();
        $filteredTotalCount = $childrenList->getTotalCount();

        foreach ($children as $child) {
            $objectTreeNode = $this->elementService->getElementTreeNodeConfig($child);
            // this if is obsolete since as long as the change with #11714 about list on line 175-179 are working fine, we already filter the list=1 there
            if ($objectTreeNode['permissions']['list'] == 1) {
                $objects[] = $objectTreeNode;
            }
        }

        //pagination for custom view
        $total = $cv
            ? $filteredTotalCount
            : $object->getChildAmount(null, $adminUser);

        return [$objects, $total, $filteredTotalCount];
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
