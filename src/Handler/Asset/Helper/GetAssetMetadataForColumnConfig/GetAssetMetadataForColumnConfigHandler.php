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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetAssetMetadataForColumnConfig;

use OpenDxp\Model\Asset;
use OpenDxp\Model\Metadata;
use OpenDxp\Security\SecurityHelper;

final class GetAssetMetadataForColumnConfigHandler
{
    public function __invoke(): GetAssetMetadataForColumnConfigResult
    {
        $result = [];

        //default metadata
        $defaultMetadataNames = ['copyright', 'alt', 'title'];
        foreach ($defaultMetadataNames as $defaultMetadata) {
            $defaultColumns[] = ['title' => $defaultMetadata, 'name' => $defaultMetadata, 'datatype' => 'data', 'fieldtype' => 'input'];
        }
        $result['defaultColumns']['nodeLabel'] = 'default_metadata';
        $result['defaultColumns']['nodeType'] = 'image';
        $result['defaultColumns']['children'] = $defaultColumns;

        //predefined metadata
        $list = Metadata\Predefined\Listing::getByTargetType('asset');
        $metadataItems = [];
        $tmp = [];
        foreach ($list as $item) {
            //only allow unique metadata columns with subtypes
            $uniqueKey = $item->getName() . '_' . $item->getTargetSubtype();
            if (!in_array($uniqueKey, $tmp) && !in_array($item->getName(), $defaultMetadataNames)) {
                $tmp[] = $uniqueKey;
                $item->expand();
                $name = SecurityHelper::convertHtmlSpecialChars($item->getName());
                $metadataItems[] = [
                    'title'     => $name,
                    'name'      => $name,
                    'subtype'   => $item->getTargetSubtype(),
                    'datatype'  => 'data',
                    'fieldtype' => $item->getType(),
                    'config'    => $item->getConfig(),
                ];
            }
        }

        $result['metadataColumns']['children'] = $metadataItems;
        $result['metadataColumns']['nodeLabel'] = 'predefined_metadata';
        $result['metadataColumns']['nodeType'] = 'metadata';

        //system columns
        $systemColumnNames = Asset\Service::GRID_SYSTEM_COLUMNS;
        $systemColumns = [];
        foreach ($systemColumnNames as $systemColumn) {
            $systemColumns[] = ['title' => $systemColumn, 'name' => $systemColumn, 'datatype' => 'data', 'fieldtype' => 'system'];
        }
        $result['systemColumns']['nodeLabel'] = 'system_columns';
        $result['systemColumns']['nodeType'] = 'system';
        $result['systemColumns']['children'] = $systemColumns;

        return new GetAssetMetadataForColumnConfigResult($result);
    }
}
