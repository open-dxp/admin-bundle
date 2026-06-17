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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Db;
use OpenDxp\Model\DataObject\Classificationstore;
use stdClass;

final class GetCollectionRelationsHandler
{
    public function __invoke(GetCollectionRelationsPayload $payload): GetCollectionRelationsResult
    {
        $mapping = ['groupName' => 'name', 'groupDescription' => 'description'];

        $orderKey = 'sorter';
        $order = 'ASC';

        if ($payload->dir !== null) {
            $order = $payload->dir;
        }

        $sortingSettings = QueryParams::extractSortingSettings($payload->queryAll);
        if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
            $orderKey = $sortingSettings['orderKey'];
            $order = $sortingSettings['order'];
        }

        if ($payload->overrideSort) {
            $orderKey = 'id';
            $order = 'DESC';
        }

        $list = new Classificationstore\CollectionGroupRelation\Listing();

        if ($payload->limit > 0) {
            $list->setLimit($payload->limit);
        }
        $list->setOffset($payload->start);
        $list->setOrder($order);
        $list->setOrderKey($mapping[$orderKey] ?? $orderKey);
        $condition = '';

        if ($payload->filter !== null) {
            $db = Db::get();
            $filters = json_decode($payload->filter);

            $count = 0;
            /** @var stdClass $f */
            foreach ($filters as $f) {
                if (!isset($f->value)) {
                    continue;
                }

                if ($count > 0) {
                    $condition .= ' AND ';
                }
                $count++;
                $fieldname = $mapping[$f->field];
                $condition .= $db->quoteIdentifier($fieldname) . ' LIKE ' . $db->quote('%' . $f->value . '%');
            }
        }

        if ($condition) {
            $condition = '( ' . $condition . ' ) AND';
        }
        $condition .= ' colId = ' . $list->quote($payload->colId);

        $list->setCondition($condition);

        $listItems = $list->load();

        $data = [];
        foreach ($listItems as $config) {
            $item = [
                'colId' => $config->getColId(),
                'groupId' => $config->getGroupId(),
                'groupName' => $config->getName(),
                'groupDescription' => $config->getDescription(),
                'id' => $config->getColId() . '-' . $config->getGroupId(),
                'sorter' => $config->getSorter(),
            ];
            $data[] = $item;
        }

        return new GetCollectionRelationsResult(data: $data, total: $list->getTotalCount());
    }
}
