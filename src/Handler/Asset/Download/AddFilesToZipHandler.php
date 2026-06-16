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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Db\Helper;
use OpenDxp\Model\Asset;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use ZipArchive;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class AddFilesToZipHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(
        int $id,
        ?string $selectedIds,
        int $offset,
        int $limit,
        string $jobId,
    ): void {
        $adminUser = $this->userContext->getAdminUser();
        $zipFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/download-zip-' . $jobId . '.zip';
        $asset = Asset::getById($id) ?? throw new AssetNotFoundException($id);

        if (!$asset->isAllowed('view')) {
            throw new AccessDeniedHttpException();
        }

        $zip = new ZipArchive();
        $zipState = is_file($zipFile) ? $zip->open($zipFile) : $zip->open($zipFile, ZipArchive::CREATE);

        if ($zipState !== true) {
            throw new \RuntimeException('Failed to open zip archive: ' . $zipFile);
        }

        $parentPath = $asset->getRealFullPath();
        if ($asset->getId() === 1) {
            $parentPath = '';
        }

        $db = \OpenDxp\Db::get();
        $conditionFilters = [];

        if (!empty($selectedIds)) {
            $selectedIdList = explode(',', $selectedIds);
            $quotedSelectedIds = [];
            foreach ($selectedIdList as $selectedId) {
                if ($selectedId) {
                    $quotedSelectedIds[] = $db->quote($selectedId);
                }
            }
            $conditionFilters[] = 'id IN (' . implode(',', $quotedSelectedIds) . ')';
        }
        $conditionFilters[] = "`type` != 'folder' AND `path` like " . $db->quote(Helper::escapeLike($parentPath) . '/%');
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
        $assetList->setOrderKey('LENGTH(`path`) ASC, id ASC', false);
        $assetList->setOffset($offset);
        $assetList->setLimit($limit);

        foreach ($assetList as $a) {
            if (!$a->isAllowed('view') || $a instanceof Asset\Folder) {
                continue;
            }
            $zip->addFile($a->getLocalFile(), preg_replace('@^' . preg_quote($asset->getRealPath(), '@') . '@i', '', $a->getRealFullPath()));
        }

        $zip->close();
    }
}
