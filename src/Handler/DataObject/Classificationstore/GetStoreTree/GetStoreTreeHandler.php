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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetStoreTree;

use OpenDxp\Model\DataObject\Classificationstore;

final class GetStoreTreeHandler
{
    public function __invoke(): GetStoreTreeResult
    {
        $result = [];
        $list = new Classificationstore\StoreConfig\Listing();
        $list = $list->load();
        foreach ($list as $item) {
            $resultItem = [
                'id' => $item->getId(),
                'text' => htmlspecialchars($item->getName() ?? '', ENT_QUOTES),
                'expandable' => false,
                'leaf' => true,
                'expanded' => true,
                'description' => htmlspecialchars($item->getDescription() ?? '', ENT_QUOTES),
                'iconCls' => 'opendxp_icon_classificationstore',
            ];

            $resultItem['qtitle'] = 'ID: ' . $item->getId();
            $resultItem['qtip'] = $item->getDescription() ? htmlspecialchars($item->getDescription(), ENT_QUOTES) : ' ';
            $result[] = $resultItem;
        }

        return new GetStoreTreeResult(items: $result);
    }
}
