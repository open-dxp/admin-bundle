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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetIdPathPagingInfo;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetIdPathPagingInfo\GetIdPathPagingInfoPayload;
use OpenDxp\Model\DataObject;

final class GetIdPathPagingInfoHandler
{
    public function __invoke(GetIdPathPagingInfoPayload $payload): GetIdPathPagingInfoResult
    {
        $path = $payload->path;
        $limit = $payload->limit;
        $pathParts = explode('/', $path);
        $id = (int) array_pop($pathParts);

        $data = [];

        $object = DataObject::getById($id);

        while ($parent = $object->getParent()) {
            $list = new DataObject\Listing();
            $list->setCondition('parentId = ?', $parent->getId());
            $list->setUnpublished(true);
            $total = $list->getTotalCount();

            $info = [
                'total' => $total,
            ];

            if ($total > $limit) {
                $idList = $list->loadIdList();
                $position = array_search($object->getId(), $idList);
                $info['position'] = $position + 1;
                $info['page'] = ceil($info['position'] / $limit);
            }

            $data[$parent->getId()] = $info;

            $object = $parent;
        }

        return new GetIdPathPagingInfoResult(data: $data);
    }
}
