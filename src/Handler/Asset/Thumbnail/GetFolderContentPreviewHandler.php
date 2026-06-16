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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Db\Helper;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element;
use OpenDxp\Model\User;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class GetFolderContentPreviewHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly GridHelperService $gridHelperService,
        private readonly ElementServiceInterface $elementService,
    ) {}

    public function __invoke(array $requestParams): GetFolderContentPreviewResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $filterPrepareEvent = new GenericEvent(null, ['requestParams' => $requestParams]);
        $this->eventDispatcher->dispatch($filterPrepareEvent, AdminEvents::ASSET_LIST_BEFORE_FILTER_PREPARE);
        $requestParams = $filterPrepareEvent->getArgument('requestParams');

        $folder = Asset::getById((int) $requestParams['id']);

        $start = (int) ($requestParams['start'] ?? 0);
        $limit = (int) ($requestParams['limit'] ?? 10);

        $conditionFilters = [];
        $list = new Asset\Listing();
        $conditionFilters[] = '`path` LIKE ' . ($folder->getRealFullPath() === '/' ? "'/%'" : $list->quote(Helper::escapeLike($folder->getRealFullPath()) . '/%')) . " AND `type` != 'folder'";

        if (!$adminUser->isAdmin()) {
            $conditionFilters[] = $this->gridHelperService->getPermittedPathsByUser('asset', $adminUser);
        }

        $list->setCondition(implode(' AND ', $conditionFilters));
        $list->setLimit($limit);
        $list->setOffset($start);
        $list->setOrderKey('CAST(filename AS CHAR CHARACTER SET utf8) COLLATE utf8_general_ci ASC', false);

        $beforeListLoadEvent = new GenericEvent(null, ['list' => $list, 'context' => $requestParams]);
        $this->eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::ASSET_LIST_BEFORE_LIST_LOAD);
        /** @var Asset\Listing $list */
        $list = $beforeListLoadEvent->getArgument('list');

        $list->load();

        $assets = [];
        foreach ($list as $asset) {
            if (!$asset->isAllowed('list')) {
                continue;
            }

            $filenameDisplay = $asset->getFilename();
            if (strlen($filenameDisplay) > 32) {
                $filenameDisplay = substr($filenameDisplay, 0, 25) . '...' . pathinfo($filenameDisplay, PATHINFO_EXTENSION);
            }

            $assets[] = [
                'id' => $asset->getId(),
                'type' => $asset->getType(),
                'filename' => $asset->getFilename(),
                'filenameDisplay' => htmlspecialchars($filenameDisplay ?? ''),
                'url' => $this->elementService->getThumbnailUrl($asset, ['origin' => 'folderPreview']),
                'idPath' => Element\Service::getIdPath($asset),
            ];
        }

        $result = ['data' => $assets, 'total' => $list->getTotalCount()];

        $afterListLoadEvent = new GenericEvent(null, ['list' => $result, 'context' => $requestParams]);
        $this->eventDispatcher->dispatch($afterListLoadEvent, AdminEvents::ASSET_LIST_AFTER_LIST_LOAD);
        $result = $afterListLoadEvent->getArgument('list');

        return new GetFolderContentPreviewResult($result['data'], $result['total']);
    }
}
