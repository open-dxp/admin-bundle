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

namespace OpenDxp\Bundle\AdminBundle\Service\Asset;

use Exception;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\AddAsset\AddAssetPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Config;
use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\Model\Asset\ResolveUploadTargetEvent;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Element;
use OpenDxp\Model\Element\ValidationException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mime\MimeTypes;

final class AssetUploadService
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly Config $config,
    ) {
    }

    /**
     * @throws Exception
     */
    public function addAsset(AddAssetPayload $payload): Asset
    {
        $defaultUploadPath = $this->config['assets']['default_upload_path'] ?? '/';

        if ($payload->filedataPath !== null) {
            $filename = $payload->filedataOriginalName;
            $sourcePath = $payload->filedataPath;
        } elseif ($payload->type === 'base64') {
            $filename = $payload->filename;
            $sourcePath = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/upload-base64' . uniqid('', false) . '.tmp';
            $data = preg_replace('@^data:[^,]+;base64,@', '', $payload->data);
            $filesystem = new Filesystem();
            $filesystem->dumpFile($sourcePath, base64_decode($data));
        } else {
            throw new Exception('The filename of the asset is empty');
        }

        $parentId = $payload->parentId;
        $parentPath = $payload->parentPath;

        if ($payload->hasDir && $payload->hasParentId) {
            $parent = Asset::getById($payload->parentId);
            $dir = $payload->dir;
            if (str_contains($dir, '..')) {
                throw new Exception('not allowed');
            }

            $newPath = $parent->getRealFullPath() . '/' . trim($dir, '/ ');

            $maxRetries = 5;
            $newParent = null;
            for ($retries = 0; $retries < $maxRetries; $retries++) {
                try {
                    $newParent = Asset\Service::createFolderByPath($newPath);

                    break;
                } catch (Exception $e) {
                    if ($retries < ($maxRetries - 1)) {
                        $waitTime = random_int(100000, 900000);
                        usleep($waitTime);
                    } else {
                        throw $e;
                    }
                }
            }
            if ($newParent) {
                $parentId = $newParent->getId();
            }
        } elseif (!$parentId && $parentPath) {
            $parent = Asset::getByPath($parentPath);
            if ($parent instanceof Asset\Folder) {
                $parentId = $parent->getId();
            } else {
                $parentId = Asset\Service::createFolderByPath($parentPath)->getId();
            }
        }

        $filename = Element\Service::getValidKey($filename, 'asset');
        if (empty($filename)) {
            throw new Exception('The filename of the asset is empty');
        }

        $context = $payload->context;
        if ($context) {
            $context = json_decode($context, true);
            $context = $context ?: [];

            $this->validateManyToManyRelationAssetType($context, $filename, $sourcePath);

            $event = new ResolveUploadTargetEvent($parentId, $filename);
            $event->setArgument('context', $context);

            OpenDxp::getEventDispatcher()->dispatch($event, AssetEvents::RESOLVE_UPLOAD_TARGET);
            $filename = Element\Service::getValidKey($event->getFilename(), 'asset');
            $parentId = $event->getParentId();
        }

        if (!$parentId) {
            $parentId = Asset\Service::createFolderByPath($defaultUploadPath)->getId();
        }

        $parentAsset = Asset::getById((int)$parentId);

        if (!$payload->allowOverwrite) {
            $filename = $this->getSafeFilename($parentAsset->getRealFullPath(), $filename);
        }

        if (!$parentAsset->isAllowed('create')) {
            throw new AccessDeniedHttpException(
                'Missing the permission to create new assets in the folder: ' . $parentAsset->getRealFullPath()
            );
        }
        if (is_file($sourcePath) && filesize($sourcePath) < 1) {
            throw new Exception('File is empty!');
        }

        if (!is_file($sourcePath)) {
            throw new Exception('Something went wrong, please check upload_max_filesize and post_max_size in your php.ini as well as the write permissions of your temporary directories.');
        }

        $uploadAssetType = $payload->uploadAssetType;
        if ($uploadAssetType) {
            $mimetype = MimeTypes::getDefault()->guessMimeType($sourcePath);
            $assetType = Asset::getTypeFromMimeMapping($mimetype, $filename);

            if ($uploadAssetType !== $assetType) {
                throw new Exception("Mime type $mimetype does not match with asset type: $uploadAssetType");
            }
        }

        $adminUser = $this->userContext->getAdminUser();

        if ($payload->allowOverwrite && Asset\Service::pathExists($parentAsset->getRealFullPath().'/'.$filename)) {
            $asset = Asset::getByPath($parentAsset->getRealFullPath().'/'.$filename);
            $asset->setStream(fopen($sourcePath, 'rb', false, \OpenDxp\File::getContext()));
            $asset->save();
        } else {
            $asset = Asset::create($parentId, [
                'filename' => $filename,
                'sourcePath' => $sourcePath,
                'userOwner' => $adminUser->getId(),
                'userModification' => $adminUser->getId(),
            ]);
        }

        @unlink($sourcePath);

        return $asset;
    }

    public function getSafeFilename(string $targetPath, string $filename): string
    {
        $pathinfo = pathinfo($filename);
        $originalFilename = $pathinfo['filename'];
        $originalFileextension = empty($pathinfo['extension']) ? '' : '.' . $pathinfo['extension'];
        $count = 1;

        if ($targetPath === '/') {
            $targetPath = '';
        }

        while (true) {
            if (Asset\Service::pathExists($targetPath . '/' . $filename)) {
                $filename = $originalFilename . '_' . $count . $originalFileextension;
                $count++;
            } else {
                return $filename;
            }
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateManyToManyRelationAssetType(array $context, string $filename, string $sourcePath): void
    {
        if (isset($context['containerType'], $context['objectId'], $context['fieldname'])
            && 'object' === $context['containerType']
            && $object = Concrete::getById($context['objectId'])
        ) {
            $fieldDefinition = $object->getClass()->getFieldDefinition($context['fieldname']);
            if (!$fieldDefinition instanceof ManyToManyRelation) {
                return;
            }

            $mimeType = MimeTypes::getDefault()->guessMimeType($sourcePath);
            $type = Asset::getTypeFromMimeMapping($mimeType, $filename);

            $allowedAssetTypes = $fieldDefinition->getAssetTypes();
            $allowedAssetTypes = array_column($allowedAssetTypes, 'assetTypes');

            if (
                !(
                    $fieldDefinition->getAssetsAllowed()
                    && ($allowedAssetTypes === [] || in_array($type, $allowedAssetTypes, true))
                )
            ) {
                throw new ValidationException(sprintf('Invalid relation in field `%s` [type: %s]', $context['fieldname'], $type));
            }
        }
    }
}
