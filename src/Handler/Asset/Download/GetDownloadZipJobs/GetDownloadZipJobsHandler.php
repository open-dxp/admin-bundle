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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\GetDownloadZipJobs;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Db\Helper;
use OpenDxp\Model\Asset;
use Symfony\Component\Routing\RouterInterface;

final class GetDownloadZipJobsHandler
{
    private const int FILES_PER_JOB = 5;

    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(GetDownloadZipJobsPayload $payload): GetDownloadZipJobsResult
    {
        $id = $payload->id;
        $selectedIds = $payload->selectedIds;
        $adminUser = $this->userContext->getAdminUser();
        $asset = Asset::getById($id) ?? throw new AssetNotFoundException($id);

        if (!$asset->isAllowed('view')) {
            return new GetDownloadZipJobsResult(jobId: uniqid('', false), jobs: []);
        }

        $parentPath = $asset->getRealFullPath();
        if ($asset->getId() == 1) {
            $parentPath = '';
        }

        $db = \OpenDxp\Db::get();
        $conditionFilters = [];
        $selectedIdList = explode(',', $selectedIds);
        $quotedSelectedIds = [];
        foreach ($selectedIdList as $selectedId) {
            if ($selectedId) {
                $quotedSelectedIds[] = $db->quote($selectedId);
            }
        }
        if ($quotedSelectedIds !== []) {
            $conditionFilters[] = 'id IN (' . implode(',', $quotedSelectedIds) . ')';
        }
        $conditionFilters[] = '`path` LIKE ' . $db->quote(Helper::escapeLike($parentPath) . '/%') . ' AND `type` != ' . $db->quote('folder');
        if (!$adminUser->isAdmin()) {
            $userIds = $adminUser->getRoles();
            $userIds[] = $adminUser->getId();
            $conditionFilters[] = ' (
               (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(CONCAT(`path`, filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
               OR
               (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(`path`, filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
            )';
        }

        $assetList = new Asset\Listing();
        $assetList->setCondition(implode(' AND ', $conditionFilters));
        $assetList->setOrderKey('LENGTH(`path`)', false);
        $assetList->setOrder('ASC');

        $totalCount = $assetList->getTotalCount();
        $jobId = uniqid('', false);
        $addFilesUrl = $this->router->generate('opendxp_admin_asset_downloadaszipaddfiles');
        $jobAmount = (int) ceil($totalCount / self::FILES_PER_JOB);
        $jobs = [];
        for ($i = 0; $i < $jobAmount; $i++) {
            $jobs[] = [[
                'url' => $addFilesUrl,
                'method' => 'GET',
                'params' => [
                    'id' => $asset->getId(),
                    'selectedIds' => implode(',', $selectedIdList),
                    'offset' => $i * self::FILES_PER_JOB,
                    'limit' => self::FILES_PER_JOB,
                    'jobId' => $jobId,
                ],
            ]];
        }

        return new GetDownloadZipJobsResult(jobId: $jobId, jobs: $jobs);
    }
}
