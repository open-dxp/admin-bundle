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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\TreeGetAssetChildren;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class TreeGetAssetChildrenHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceInterface $elementService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(TreeGetAssetChildrenPayload $payload): TreeGetAssetChildrenPaginatedResult|TreeGetAssetChildrenListResult
    {
        $nodeId = $payload->nodeId;
        $customViewId = $payload->customViewId;
        $filter = $payload->filter;
        $limit = $payload->limit;
        $offset = $payload->offset;
        $asset = Asset::getById($nodeId);
        if (!$asset instanceof Asset) {
            throw new AssetNotFoundException($nodeId);
        }

        $adminUser = $this->userContext->getAdminUser();
        $assets = [];
        $cv = [];
        $filteredTotalCount = 0;

        $sqlFilter = $filter;
        if ($sqlFilter !== null) {
            if (!str_ends_with($sqlFilter, '*')) {
                $sqlFilter .= '*';
            }
            $sqlFilter = str_replace('*', '%', $sqlFilter);
            $limit = 100;
            $offset = 0;
        }

        if ($asset->hasChildren()) {
            if ($customViewId) {
                $cv = $this->elementService->getCustomViewById($customViewId);
            }

            $childrenList = new Asset\Listing();
            $childrenList->addConditionParam('parentId = ?', [$asset->getId()]);
            $childrenList->filterAccessibleByUser($adminUser, $asset);

            if ($sqlFilter !== null) {
                $childrenList->addConditionParam('CAST(assets.filename AS CHAR CHARACTER SET utf8) COLLATE utf8_general_ci LIKE ?', [$sqlFilter]);
            }

            $childrenList->setLimit($limit);
            $childrenList->setOffset($offset);
            $childrenList->setOrderKey("FIELD(assets.type, 'folder') DESC, CAST(assets.filename AS CHAR CHARACTER SET utf8) COLLATE utf8_general_ci ASC", false);

            Element\Service::addTreeFilterJoins($cv, $childrenList);

            $beforeListLoadEvent = new GenericEvent(null, [
                'list' => $childrenList,
                'context' => $payload->queryAll,
            ]);
            $this->eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::ASSET_LIST_BEFORE_LIST_LOAD);
            /** @var Asset\Listing $childrenList */
            $childrenList = $beforeListLoadEvent->getArgument('list');

            $children = $childrenList->load();
            $filteredTotalCount = $childrenList->getTotalCount();

            foreach ($children as $childAsset) {
                $assetTreeNode = $this->elementService->getElementTreeNodeConfig($childAsset);
                if ($assetTreeNode['permissions']['list'] == 1) {
                    $assets[] = $assetTreeNode;
                }
            }
        }

        $event = new GenericEvent(null, ['assets' => $assets]);
        $this->eventDispatcher->dispatch($event, AdminEvents::ASSET_TREE_GET_CHILDREN_BY_ID_PRE_SEND_DATA);
        $eventAssets = $event->getArgument('assets');
        $assets = is_array($eventAssets) ? $eventAssets : $assets;

        if (!$payload->hasPagination()) {
            return new TreeGetAssetChildrenListResult($assets);
        }

        return new TreeGetAssetChildrenPaginatedResult(
            nodes: $assets,
            offset: $offset,
            limit: $limit,
            total: $asset->getChildAmount($adminUser),
            overflow: $filter !== null && ($filteredTotalCount > $limit),
            filter: $filter ?? '',
            inSearch: $payload->inSearch,
        );
    }
}
