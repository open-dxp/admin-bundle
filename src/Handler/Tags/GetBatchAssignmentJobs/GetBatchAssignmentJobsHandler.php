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

namespace OpenDxp\Bundle\AdminBundle\Handler\Tags\GetBatchAssignmentJobs;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetBatchAssignmentJobsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(GetBatchAssignmentJobsPayload $payload): GetBatchAssignmentJobsResult
    {
        $elementType = $payload->elementType;
        $elementId = $payload->elementId;
        $adminUser = $this->userContext->getAdminUser();
        $userIds = null;
        if (!$adminUser?->isAdmin()) {
            $userIds = $adminUser?->getRoles() ?? [];
            $userIds[] = $adminUser?->getId();
        }
        $idList = [];

        switch ($elementType) {
            case 'object':
                $object = DataObject::getById($elementId);
                if ($object) {
                    $idList = $this->getSubObjectIds($object, $userIds);
                }
                break;

            case 'asset':
                $asset = Asset::getById($elementId);
                if ($asset) {
                    $idList = $this->getSubAssetIds($asset, $userIds);
                }
                break;

            case 'document':
                $document = Document::getById($elementId);
                if ($document) {
                    $idList = $this->getSubDocumentIds($document, $userIds);
                }
                break;
        }

        $size = 2;
        $offset = 0;
        $idListParts = [];
        while ($offset < count($idList)) {
            $idListParts[] = array_slice($idList, $offset, $size);
            $offset += $size;
        }

        return new GetBatchAssignmentJobsResult(idLists: $idListParts, totalCount: count($idList));
    }

    /**
     * @param int[]|null $userIds
     *
     * @return int[]
     */
    private function getSubObjectIds(DataObject\AbstractObject $object, ?array $userIds): array
    {
        $childrenList = new DataObject\Listing();
        $condition = '`path` LIKE ?';
        if ($userIds !== null) {
            $condition .= ' AND (
                (SELECT `view` FROM users_workspaces_object WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(CONCAT(`path`,`key`),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                    OR
                (SELECT `view` FROM users_workspaces_object WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(`path`,`key`))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
             )';
        }

        $childrenList->setCondition($condition, $childrenList->escapeLike($object->getRealFullPath()) . '/%');

        $beforeListLoadEvent = new GenericEvent(null, [
            'list' => $childrenList,
            'context' => [],
        ]);
        $this->eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::OBJECT_LIST_BEFORE_LIST_LOAD);
        /** @var DataObject\Listing $childrenList */
        $childrenList = $beforeListLoadEvent->getArgument('list');

        return $childrenList->loadIdList();
    }

    /**
     * @param int[]|null $userIds
     *
     * @return int[]
     */
    private function getSubAssetIds(Asset $asset, ?array $userIds): array
    {
        $childrenList = new Asset\Listing();
        $condition = '`path` LIKE ?';
        if ($userIds !== null) {
            $condition .= ' AND (
                (SELECT `view` FROM users_workspaces_asset WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(CONCAT(`path`,filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                    OR
                (SELECT `view` FROM users_workspaces_asset WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(`path`,filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
            )';
        }

        $childrenList->setCondition($condition, $childrenList->escapeLike($asset->getRealFullPath()) . '/%');

        $beforeListLoadEvent = new GenericEvent(null, [
            'list' => $childrenList,
            'context' => [],
        ]);
        $this->eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::ASSET_LIST_BEFORE_LIST_LOAD);
        /** @var Asset\Listing $childrenList */
        $childrenList = $beforeListLoadEvent->getArgument('list');

        return $childrenList->loadIdList();
    }

    /**
     * @param int[]|null $userIds
     *
     * @return int[]
     */
    private function getSubDocumentIds(Document $document, ?array $userIds): array
    {
        $childrenList = new Document\Listing();
        $condition = '`path` LIKE ?';
        if ($userIds !== null) {
            $condition .= ' AND (
                (SELECT `view` FROM users_workspaces_document WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(CONCAT(`path`,`key`),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                    OR
                (SELECT `view` FROM users_workspaces_document WHERE userId IN (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(`path`,`key`))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
            )';
        }

        $childrenList->setCondition($condition, $childrenList->escapeLike($document->getRealFullPath()) . '/%');

        $beforeListLoadEvent = new GenericEvent(null, [
            'list' => $childrenList,
            'context' => [],
        ]);
        $this->eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::DOCUMENT_LIST_BEFORE_LIST_LOAD);
        /** @var Document\Listing $childrenList */
        $childrenList = $beforeListLoadEvent->getArgument('list');

        return $childrenList->loadIdList();
    }
}
