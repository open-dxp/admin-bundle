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

final class GetPropertiesHandler
{
    public function __invoke(GetPropertiesPayload $payload): GetPropertiesResult
    {
        $db = Db::get();

        $conditionParts = [];

        if ($payload->frameName) {
            $keyCriteria = ' FALSE ';
            $frameConfig = Classificationstore\CollectionConfig::getByName($payload->frameName, $payload->storeId);
            if ($frameConfig) {
                // get all keys within that collection / frame
                $frameId = $frameConfig->getId();
                $groupList = new Classificationstore\CollectionGroupRelation\Listing();
                $groupList->setCondition('colId = ' . $db->quote($frameId));
                $groupList = $groupList->load();
                $groupIdList = [];
                foreach ($groupList as $groupEntry) {
                    $groupIdList[] = $groupEntry->getGroupId();
                }

                if ($groupIdList) {
                    $keyIdList = new Classificationstore\KeyGroupRelation\Listing();
                    $keyIdList->setCondition('groupId in (' . implode(',', $groupIdList) . ')');
                    $keyIdList = $keyIdList->load();
                    if ($keyIdList) {
                        $keyIdValues = [];
                        foreach ($keyIdList as $keyEntry) {
                            $keyIdValues[] = $keyEntry->getKeyId();
                        }

                        $keyCriteria = ' id in (' . implode(',', $keyIdValues) . ')';
                    }
                }
            }

            $conditionParts[] = $keyCriteria;
        }

        $orderKey = 'name';
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

        $list = new Classificationstore\KeyConfig\Listing();

        if ($payload->limit > 0 && !$payload->groupIds && !$payload->keyIds) {
            $list->setLimit($payload->limit);
        }
        $list->setOffset($payload->start);
        $list->setOrder($order);
        $list->setOrderKey($orderKey);

        if ($payload->searchfilter) {
            $conditionParts[] = '(name LIKE ' . $db->quote('%' . $payload->searchfilter . '%') . ' OR description LIKE ' . $db->quote('%' . $payload->searchfilter . '%') . ')';
        }

        if ($payload->storeId) {
            $conditionParts[] = '(storeId = ' . $db->quote($payload->storeId) . ')';
        }

        if ($payload->filter !== null) {
            $filters = json_decode($payload->filter);
            /** @var stdClass $f */
            foreach ($filters as $f) {
                if (!isset($f->value)) {
                    continue;
                }

                $conditionParts[] = $db->quoteIdentifier($f->property) . ' LIKE ' . $db->quote('%' . $f->value . '%');
            }
        }
        $condition = implode(' AND ', $conditionParts);
        $list->setCondition($condition);

        if ($payload->groupIds || $payload->keyIds) {
            if ($payload->groupIds) {
                $ids = json_decode($payload->groupIds, true);
                $col = 'group';
            } else {
                $ids = json_decode($payload->keyIds, true);
                $col = 'id';
            }

            $condition = $db->quoteIdentifier($col) . ' IN (';
            $count = 0;
            foreach ($ids as $theId) {
                if ($count > 0) {
                    $condition .= ',';
                }
                $condition .= $theId;
                $count++;
            }

            $condition .= ')';
            $list->setCondition($condition);
        }

        $list->load();
        $configList = $list->getList();

        $data = [];
        foreach ($configList as $config) {
            $data[] = self::buildKeyConfigItem($config);
        }

        return new GetPropertiesResult(data: $data, total: $list->getTotalCount());
    }

    public static function buildKeyConfigItem(Classificationstore\KeyConfig $config): array
    {
        $name = $config->getName();

        $item = [
            'storeId' => $config->getStoreId(),
            'id' => $config->getId(),
            'name' => $name,
            'description' => $config->getDescription(),
        ];

        if ($config->getCreationDate()) {
            $item['creationDate'] = $config->getCreationDate();
        }

        if ($config->getModificationDate()) {
            $item['modificationDate'] = $config->getModificationDate();
        }

        $item['type'] = $config->getType() ?: 'input';
        $definition = $config->getDefinition();
        $item['definition'] = $definition;

        if ($definition) {
            $definition = json_decode($definition, true);
            if ($definition) {
                $item['title'] = $definition['title'];
            }
        }

        return $item;
    }
}
