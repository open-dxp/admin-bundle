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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings;

use OpenDxp\Model\Asset;

final class GetThumbnailTreeHandler
{
    public function __invoke(): GetThumbnailTreeResult
    {
        $thumbnails = [];
        $list = new Asset\Image\Thumbnail\Config\Listing();
        $groups = [];

        foreach ($list->getThumbnails() as $item) {
            if ($item->getGroup()) {
                if (empty($groups[$item->getGroup()])) {
                    $groups[$item->getGroup()] = [
                        'id' => 'group_' . $item->getName(),
                        'text' => htmlspecialchars($item->getGroup()),
                        'expandable' => true,
                        'leaf' => false,
                        'allowChildren' => true,
                        'iconCls' => 'opendxp_icon_folder',
                        'group' => $item->getGroup(),
                        'children' => [],
                    ];
                }
                $groups[$item->getGroup()]['children'][] = [
                    'id' => $item->getName(),
                    'text' => $item->getName(),
                    'leaf' => true,
                    'iconCls' => 'opendxp_icon_thumbnails',
                    'cls' => 'opendxp_treenode_disabled',
                    'writeable' => $item->isWriteable(),
                ];
            } else {
                $thumbnails[] = [
                    'id' => $item->getName(),
                    'text' => $item->getName(),
                    'leaf' => true,
                    'iconCls' => 'opendxp_icon_thumbnails',
                    'cls' => 'opendxp_treenode_disabled',
                    'writeable' => $item->isWriteable(),
                ];
            }
        }

        foreach ($groups as $group) {
            $thumbnails[] = $group;
        }

        return new GetThumbnailTreeResult(nodes: $thumbnails);
    }
}
