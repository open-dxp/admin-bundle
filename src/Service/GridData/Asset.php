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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Service\GridData;

use OpenDxp;
use OpenDxp\Model;
use OpenDxp\Model\Asset\MetaData\ClassDefinition\Data\Data;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Exception\UnsupportedException;

/**
 *
 * @internal
 */
class Asset extends Element
{
    public static function getData(Model\Asset $asset, ?array $fields = null, ?string $requestedLanguage = null, array $params = []): array
    {
        $data = self::gridElementData($asset);
        $loader = null;

        if (!empty($fields)) {
            $data = [
                'id' => $asset->getId(),
                'id~system' => $asset->getId(),
                'type~system' => $asset->getType(),
                'fullpath~system' => $asset->getRealFullPath(),
                'filename~system' => $asset->getKey(),
                'creationDate~system' => $asset->getCreationDate(),
                'modificationDate~system' => $asset->getModificationDate(),
                'idPath~system' => Service::getIdPath($asset),
            ];

            $requestedLanguage = str_replace('default', '', $requestedLanguage);

            foreach ($fields as $field) {
                $fieldDef = explode('~', $field);
                if (isset($fieldDef[1]) && $fieldDef[1] === 'system') {
                    if ($fieldDef[0] === 'preview') {
                        $data[$field] = self::getPreviewThumbnail($asset, ['treepreview' => true, 'width' => 108, 'height' => 70, 'frame' => true]);
                    } elseif ($fieldDef[0] === 'size') {
                        $size = $asset->getFileSize();
                        $data[$field] = formatBytes($size);
                    }
                } else {
                    if (isset($fieldDef[1])) {
                        $language = ($fieldDef[1] === 'none' ? '' : $fieldDef[1]);
                        $rawMetaData = $asset->getMetadata($fieldDef[0], $language, true, true);
                    } else {
                        $rawMetaData = $asset->getMetadata($field, $requestedLanguage, true, true);
                    }

                    $metaData = $rawMetaData['data'] ?? null;

                    if ($rawMetaData) {
                        $type = $rawMetaData['type'];
                        if (!$loader) {
                            $loader = OpenDxp::getContainer()->get('opendxp.implementation_loader.asset.metadata.data');
                        }

                        $metaData = $rawMetaData['data'] ?? null;

                        try {
                            /** @var Data $instance */
                            $instance = $loader->build($type);
                            $metaData = $instance->getDataForListfolderGrid($rawMetaData['data'] ?? null, $rawMetaData);
                        } catch (UnsupportedException) {
                        }
                    }

                    $data[$field] = $metaData;
                }
            }
        }

        return $data;
    }

    public static function getPreviewThumbnail(Model\Asset $asset, array $params = [], bool $onlyMethod = false): ?string
    {
        $thumbnailMethod = '';
        $thumbnailUrl = null;

        if ($asset instanceof Model\Asset\Image) {
            $thumbnailMethod = 'getThumbnail';
        } elseif ($asset instanceof Model\Asset\Video && \OpenDxp\Video::isAvailable()) {
            $thumbnailMethod = 'getImageThumbnail';
        } elseif ($asset instanceof Model\Asset\Document && \OpenDxp\Document::isAvailable()) {
            $thumbnailMethod = 'getImageThumbnail';
        }

        if ($onlyMethod) {
            return $thumbnailMethod;
        }

        if (!empty($thumbnailMethod)) {
            $thumbnailUrl = '/admin/asset/get-' . $asset->getType() . '-thumbnail?id=' . $asset->getId();
            if (count($params) > 0) {
                $thumbnailUrl .= '&' . http_build_query($params);
            }
        }

        return $thumbnailUrl;
    }
}
