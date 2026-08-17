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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\TreeGetDocumentChildren;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementServiceInterface;
use OpenDxp\Db;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\Service;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TreeGetDocumentChildrenHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceInterface $elementService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {
    }

    public function __invoke(TreeGetDocumentChildrenPayload $payload): TreeGetDocumentChildrenPaginatedResult|TreeGetDocumentChildrenListResult
    {
        $limit = (int)($payload->allParams['limit'] ?? 100000000);
        $offset = (int)($payload->allParams['start'] ?? 0);

        $filter = $payload->allParams['filter'] ?? null;
        $sqlFilter = $filter;
        if (!is_null($sqlFilter)) {
            if (!str_ends_with($sqlFilter, '*')) {
                $sqlFilter .= '*';
            }
            $sqlFilter = str_replace('*', '%', $sqlFilter);
            $limit = 100;
            $offset = 0;
        }

        $document = Document::getById((int) $payload->allParams['node']);
        if (!$document) {
            throw new NotFoundHttpException('Document was not found');
        }

        $adminUser = $this->userContext->getAdminUser();
        $documents = [];
        $cv = [];
        if ($document->hasChildren()) {
            if ($payload->allParams['view'] ?? null) {
                $cv = $this->elementService->getCustomViewById($payload->allParams['view']);
            }

            $db = Db::get();
            $list = new Document\Listing();
            $condition = 'parentId =  ' . $db->quote($document->getId());
            if (!$adminUser->isAdmin()) {
                $userIds = $adminUser->getRoles();
                $currentUserId = $adminUser->getId();
                $userIds[] = $currentUserId;

                $inheritedPermission = $document->getDao()->isInheritingPermission('list', $userIds);

                $anyAllowedRowOrChildren = 'EXISTS(SELECT list FROM users_workspaces_document uwd WHERE userId IN (' . implode(',', $userIds) . ') AND list=1 AND LOCATE(CONCAT(`path`,`key`),cpath)=1 AND
                NOT EXISTS(SELECT list FROM users_workspaces_document WHERE userId =' . $currentUserId . '  AND list=0 AND cpath = uwd.cpath))';
                $isDisallowedCurrentRow = 'EXISTS(SELECT list FROM users_workspaces_document WHERE userId IN (' . implode(',', $userIds) . ')  AND cid = id AND list=0)';

                $condition .= ' AND IF(' . $anyAllowedRowOrChildren . ',1,IF(' . $inheritedPermission . ', ' . $isDisallowedCurrentRow . ' = 0, 0)) = 1';
            }

            if ($sqlFilter) {
                $condition = '(' . $condition . ')' . ' AND CAST(documents.key AS CHAR CHARACTER SET utf8) COLLATE utf8_general_ci LIKE ' . $db->quote($sqlFilter);
            }

            $list->setCondition($condition);
            $list->setOrderKey(['index', 'id']);
            $list->setOrder(['asc', 'asc']);
            $list->setLimit($limit);
            $list->setOffset($offset);

            Service::addTreeFilterJoins($cv, $list);

            $beforeListLoadEvent = new GenericEvent($this->currentControllerContext->getController(), [
                'list' => $list,
                'context' => $payload->allParams,
            ]);
            $this->eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::DOCUMENT_LIST_BEFORE_LIST_LOAD);

            /** @var Document\Listing $list */
            $list = $beforeListLoadEvent->getArgument('list');

            $documents = $this->loadChildren($list);
        }

        $event = new GenericEvent($this->currentControllerContext->getController(), ['documents' => $documents]);
        $this->eventDispatcher->dispatch($event, AdminEvents::DOCUMENT_TREE_GET_CHILDREN_BY_ID_PRE_SEND_DATA);
        $documents = $event->getArgument('documents');

        if (!$payload->hasPagination()) {
            return new TreeGetDocumentChildrenListResult($documents);
        }

        return new TreeGetDocumentChildrenPaginatedResult(
            nodes: $documents,
            offset: $offset,
            limit: $limit,
            total: $document->getChildAmount($adminUser),
            filter: $filter ?? '',
            inSearch: $payload->inSearch,
        );
    }

    private function loadChildren(Document\Listing $list): array
    {
        $documents = [];
        foreach ($list->getDocuments() as $childDocument) {
            $documentTreeNode = $this->elementService->getElementTreeNodeConfig($childDocument);
            // the !isset is for printContainer case, there are no permissions set there
            if (!isset($documentTreeNode['permissions']['list']) || $documentTreeNode['permissions']['list'] == 1) {
                $documents[] = $documentTreeNode;
            }
        }

        return $documents;
    }
}
