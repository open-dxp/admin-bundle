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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetFolderThumbnail;

use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetFolderThumbnail\GetFolderThumbnailPayload;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetFolderThumbnailHandler
{
    public function __invoke(GetFolderThumbnailPayload $payload): FolderThumbnailResult
    {
        $id = $payload->id;
        if ($id === null) {
            throw new NotFoundHttpException('could not load asset folder');
        }

        $folder = Asset\Folder::getById($id);
        if (!$folder instanceof Asset\Folder) {
            throw new NotFoundHttpException('could not load asset folder');
        }

        if (!$folder->isAllowed('view')) {
            throw new AccessDeniedHttpException('not allowed to view thumbnail');
        }

        $stream = $folder->getPreviewImage();
        if (!$stream) {
            throw new NotFoundHttpException(sprintf('Tree preview thumbnail not available for asset %s', $folder->getId()));
        }

        return new FolderThumbnailResult($stream);
    }
}
