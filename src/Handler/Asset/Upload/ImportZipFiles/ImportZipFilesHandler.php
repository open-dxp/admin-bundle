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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZipFiles;

use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetUploadService;
use OpenDxp\File;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class ImportZipFilesHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly AssetUploadService $assetUploadService,
        private readonly Filesystem $filesystem,
    ) {}

    public function __invoke(ImportZipFilesPayload $payload): void
    {
        $parentId = $payload->parentId;
        $jobId = $payload->jobId;
        $offset = $payload->offset;
        $limit = $payload->limit;
        $allowOverwrite = $payload->allowOverwrite;
        $isLast = $payload->isLast;
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $importAsset = Asset::getById($parentId);
        $zipFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/' . $jobId . '.zip';
        $tmpDir = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/zip-import';

        if (!is_dir($tmpDir)) {
            $this->filesystem->mkdir($tmpDir);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            return;
        }

        for ($i = $offset; $i < ($offset + $limit); $i++) {
            $path = $zip->getNameIndex($i);
            if ($path === false) {
                continue;
            }
            if (str_starts_with($path, '__MACOSX/')) {
                continue;
            }
            if (str_ends_with($path, '/Thumbs.db')) {
                continue;
            }
            if (str_ends_with($path, '/.DS_Store')) {
                continue;
            }

            if ($zip->extractTo($tmpDir . '/', $path)) {
                $tmpFile = $tmpDir . '/' . preg_replace('@^/@', '', $path);
                $filename = Element\Service::getValidKey(basename($path), 'asset');
                $relativePath = '';
                if (dirname($path) !== '.') {
                    $relativePath = dirname($path);
                }
                $parentPath = $importAsset->getRealFullPath() . '/' . preg_replace('@^/@', '', $relativePath);
                $parent = Asset\Service::createFolderByPath($parentPath);

                if (!$allowOverwrite) {
                    $filename = $this->assetUploadService->getSafeFilename($parent->getRealFullPath(), $filename);
                }

                if ($parent->isAllowed('create')) {
                    if ($allowOverwrite && Asset\Service::pathExists($parent->getRealFullPath() . '/' . $filename)) {
                        $asset = Asset::getByPath($parent->getRealFullPath() . '/' . $filename);
                        $asset->setStream(fopen($tmpFile, 'rb', false, File::getContext()));
                        $asset->save();
                    } else {
                        Asset::create($parent->getId(), [
                            'filename' => $filename,
                            'sourcePath' => $tmpFile,
                            'userOwner' => $userId,
                            'userModification' => $userId,
                        ]);
                    }

                    @unlink($tmpFile);
                } else {
                    Logger::debug('prevented creating asset because of missing permissions');
                }
            }
        }

        $zip->close();

        if ($isLast) {
            unlink($zipFile);
        }
    }
}
