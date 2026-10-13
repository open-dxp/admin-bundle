<?php

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

namespace OpenDxp\Bundle\AdminBundle\Service\Asset;

use OpenDxp\Db;
use OpenDxp\Db\Helper;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Asset\Image;
use OpenDxp\Model\Asset\Image\Thumbnail\Config;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class AssetDownloadService
{
    public function getDownloadableThumbnailConfig(string $name): Config
    {
        $config = Config::getByName($name);

        if (!$config instanceof Config || !$config->isDownloadable()) {
            throw new BadRequestHttpException(sprintf('The thumbnail "%s" is not offered for download.', $name));
        }

        return $config;
    }

    public function getThumbnail(Image $image, Config $config): Image\ThumbnailInterface
    {
        $thumbnail = $image->getThumbnail($config);
        $thumbnailConfig = $thumbnail->getConfig();
        $autoFormatConfigs = $thumbnailConfig?->getAutoFormatThumbnailConfigs() ?? [];

        if ($thumbnailConfig?->getFormat() === 'SOURCE' && $autoFormatConfigs !== []) {
            return $image->getThumbnail(current($autoFormatConfigs));
        }

        return $thumbnail;
    }

    /**
     * @param list<int> $selectedIds
     */
    public function getZipFileListing(Asset $folder, array $selectedIds, User $user): Asset\Listing
    {
        return $this->getZipListing($folder, $selectedIds, $user, "`type` != 'folder'");
    }

    /**
     * @param list<int> $selectedIds
     */
    public function getZipImageListing(Asset $folder, array $selectedIds, User $user): Asset\Listing
    {
        return $this->getZipListing($folder, $selectedIds, $user, "`type` = 'image'");
    }

    /**
     * @param list<int> $selectedIds
     */
    private function getZipListing(
        Asset $folder,
        array $selectedIds,
        User $user,
        string $typeCondition,
    ): Asset\Listing
    {
        $db = Db::get();
        $parentPath = $folder->getId() === 1 ? '' : $folder->getRealFullPath();

        $conditions = [
            '`path` LIKE ' . $db->quote(Helper::escapeLike($parentPath) . '/%'),
            $typeCondition,
        ];

        if ($selectedIds !== []) {
            $conditions[] = 'id IN (' . implode(',', $selectedIds) . ')';
        }

        if (!$user->isAdmin()) {
            $userIds = implode(',', [...$user->getRoles(), $user->getId()]);
            $conditions[] = ' (
               (select list from users_workspaces_asset where userId in (' . $userIds . ') and LOCATE(CONCAT(`path`, filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
               OR
               (select list from users_workspaces_asset where userId in (' . $userIds . ') and LOCATE(cpath,CONCAT(`path`, filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
            )';
        }

        $listing = new Asset\Listing();
        $listing->setCondition(implode(' AND ', $conditions));
        $listing->setOrderKey('LENGTH(`path`) ASC, id ASC', false);

        return $listing;
    }
}
